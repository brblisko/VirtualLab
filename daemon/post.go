/**
 * Application    VirtualLab
 * Author         Boris Vesely
 */

package main

import (
	"fmt"
	"net/http"
	"os"
	"os/user"
	"path/filepath"
	"strconv"
	"time"

	"github.com/gin-gonic/gin"
)

func changeOwner(dir string) error {
	u, err := user.Lookup("student")
	if err != nil {
		fmt.Printf("Error getting user information: %v\n", err)
		return err
	}

	uid, err := strconv.Atoi(u.Uid)
	if err != nil {
		fmt.Printf("Error converting UID to integer: %v\n", err)
		return err
	}

	gid, err := strconv.Atoi(u.Gid)
	if err != nil {
		fmt.Printf("Error converting GID to integer: %v\n", err)
		return err
	}

	err = os.Chown(dir, uid, gid)
	if err != nil {
		fmt.Printf("Error changing ownership: %v\n", err)
		return err
	}
	return nil
}

func checkDir(dir string) error {
	dir = filepath.Join(userDataDir, dir)

	_, err := os.Stat(dir)
	if err != nil {
		if os.IsNotExist(err) {
			err := os.MkdirAll(dir, os.ModePerm)
			if err != nil {
				fmt.Printf("Error creating directory: %v\n", err)
				return err
			}

			err = changeOwner(dir)
			if err != nil {
				fmt.Printf("Error changing owner of directory: %v\n", err)
			}

		} else {
			fmt.Printf("Error checking directory: %v\n", err)
			return err
		}
	}
	return nil 
}

func mount(instruction Instruction) error {
	err := checkDir(instruction.User)
	if err != nil {
		return err
	}

	err = connect(instruction.FPGAIP)
	if err != nil {
		return err
	}	
	// Open a new session
	session, err := client.NewSession()
	if err != nil {
		fmt.Println("Failed to create session:", err)
		return err
	}
	defer session.Close()

	mountDir := filepath.Join(userDataDir, instruction.User)

	fmt.Println("mounting: ", mountDir)

	// Run the command on the remote server
	output, err := session.CombinedOutput("/root/mount.sh " + mountDir)
	if err != nil {
		fmt.Println("Failed to run command:", err)
		return err
	}
	fmt.Printf(string(output))
	client.Close()
	return nil
}

func unmount(instruction Instruction) error {
	err := connect(instruction.FPGAIP)
	if err != nil {
		return err
	}

	// Open a new session
	session, err := client.NewSession()
	if err != nil {
		fmt.Println("Failed to create session:", err)
		return err
	}
	defer session.Close()

	// Run the command on the remote server
	output, err := session.CombinedOutput("/root/unmount.sh")
	if err != nil {
		fmt.Println("Failed to run command:", err)
		return err
	}
	fmt.Printf(string(output))

	client.Close()
	return nil
}

func newTunnel(instruction Instruction) {

	var tmpTunnel = Tunnel{
		FPGAIP:   instruction.FPGAIP,
		ClientIP: instruction.ClientIP,
		User:     instruction.User,

		Timestamp: time.Now().Unix(),
	}

	for i := 0; i < len(FPGAs); i++ {
		if FPGAs[i].IP == instruction.FPGAIP {
			tmpTunnel.Port = FPGAs[i].Port
			FPGAs[i].State = "TUNNEL"
		}
	}
	tunnels = append(tunnels, tmpTunnel)
}

func deleteTunnel(instruction Instruction) {
	for i := 0; i < len(tunnels); i++ {
		if (tunnels[i].FPGAIP == instruction.FPGAIP) && (tunnels[i].ClientIP == instruction.ClientIP) {
			tunnels[i] = tunnels[len(tunnels)-1]
			tunnels[len(tunnels)-1] = Tunnel{}
			tunnels = tunnels[:len(tunnels)-1]
		}
	}

	for i := 0; i < len(FPGAs); i++ {
		if FPGAs[i].IP == instruction.FPGAIP {
			FPGAs[i].State = "DEFAULT"
		}
	}
}

func instructionCreate(instruction Instruction) ErrorInternal {
	err := mount(instruction)
	if err != nil {
		return ErrorInternal{ErrorCode: 1, Message: err.Error()}
	}

	table := "filter"
	chain := "FORWARD"
	ruleSpec := []string{
		"-i", common.ClientInterface,
		"-o", common.PYNQInterface,
		"-s", instruction.ClientIP,
		"-d", instruction.FPGAIP,
		"-j", "ACCEPT",
	}

	err = ipt.Append(table, chain, ruleSpec...)
	if err != nil {
		return ErrorInternal{ErrorCode: 1, Message: err.Error()}
	}

	ruleSpec = []string{
		"-i", common.PYNQInterface,
		"-o", common.ClientInterface,
		"-s", instruction.FPGAIP,
		"-d", instruction.ClientIP,
		"-j", "ACCEPT",
	}

	err = ipt.Append(table, chain, ruleSpec...)
	if err != nil {
		return ErrorInternal{ErrorCode: 1, Message: err.Error()}
	}

	newTunnel(instruction)

	return ErrorInternal{ErrorCode: 0, Message: ""}
}

func instructionDelete(instruction Instruction) ErrorInternal {
	
	err = unmount(instruction)
	if err != nil {
		return ErrorInternal{ErrorCode: 1, Message: err.Error()}
	}

	table := "filter"
	chain := "FORWARD"
	ruleSpec := []string{
		"-i", common.ClientInterface,
		"-o", common.PYNQInterface,
		"-s", instruction.ClientIP,
		"-d", instruction.FPGAIP,
		"-j", "ACCEPT",
	}

	err = ipt.Delete(table, chain, ruleSpec...)
	if err != nil {
		return ErrorInternal{ErrorCode: 1, Message: err.Error()}
	}

	ruleSpec = []string{
		"-i", common.PYNQInterface,
		"-o", common.ClientInterface,
		"-s", instruction.FPGAIP,
		"-d", instruction.ClientIP,
		"-j", "ACCEPT",
	}

	err = ipt.Delete(table, chain, ruleSpec...)
	if err != nil {
		return ErrorInternal{ErrorCode: 1, Message: err.Error()}
	}

	deleteTunnel(instruction)

	return ErrorInternal{ErrorCode: 0, Message: ""}
}

func parseInstruction(instruction Instruction) ErrorInternal {
	if instruction.Type == "CREATE" {
		err := instructionCreate(instruction)
		if err.ErrorCode != OK {
			return err
		}

		return ErrorInternal{ErrorCode: 0, Message: ""}
	}

	if instruction.Type == "DELETE" {
		err := instructionDelete(instruction)
		if err.ErrorCode != OK {
			return err
		}

		return ErrorInternal{ErrorCode: 0, Message: ""}
	}

	return ErrorInternal{ErrorCode: UNKNOWN_INST, Message: "unknown instruction"}
}

func postInstruction(c *gin.Context) {
	var instruction Instruction

	if err := c.BindJSON(&instruction); err != nil {
		c.JSON(http.StatusBadRequest, err)
		return
	}

	if err := parseInstruction(instruction); err.ErrorCode != OK {

		c.JSON(http.StatusBadRequest, err)
		return
	}

	c.JSON(http.StatusCreated, instruction)
}

func postState(c *gin.Context) {
	var state State

	if err := c.BindJSON(&state); err != nil {
		c.JSON(http.StatusBadRequest, err)
		return
	}

	for i := 0; i < len(FPGAs); i++ {
		if FPGAs[i].IP == state.FPGAIP {
			FPGAs[i].State = state.State
		}
	}

	c.JSON(http.StatusOK, state)
}