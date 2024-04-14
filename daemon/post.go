package main

import (
	"fmt"
	"net/http"
	"os"
	"os/user"
	"strconv"
	"time"

	"github.com/gin-gonic/gin"
)

func changeOwner(dir string) {
	u, err := user.Lookup("student")
	if err != nil {
		fmt.Printf("Error getting user information: %v\n", err)
		return
	}

	uid, err := strconv.Atoi(u.Uid)
	if err != nil {
		fmt.Printf("Error converting UID to integer: %v\n", err)
		return
	}

	gid, err := strconv.Atoi(u.Gid)
	if err != nil {
		fmt.Printf("Error converting GID to integer: %v\n", err)
		return
	}

	err = os.Chown(dir, uid, gid)
	if err != nil {
		fmt.Printf("Error changing ownership: %v\n", err)
		return
	}

}

func checkDir(dir string) {
	dir = "/UserData/" + dir

	_, err := os.Stat(dir)
	if err != nil {
		if os.IsNotExist(err) {
			err := os.MkdirAll(dir, os.ModePerm)
			if err != nil {
				fmt.Printf("Error creating directory: %v\n", err)
				return
			}

			changeOwner(dir)

		} else {
			fmt.Printf("Error checking directory: %v\n", err)
		}
		return
	}

}

func mount(instruction Instruction) {
	checkDir(instruction.User)

	connect(instruction.FPGAIP)

	// Open a new session
	session, err := client.NewSession()
	if err != nil {
		fmt.Println("Failed to create session:", err)
		os.Exit(1)
	}
	defer session.Close()

	// Run the command on the remote server
	output, err := session.CombinedOutput("/root/mount.sh " + instruction.User)
	if err != nil {
		fmt.Println("Failed to run command:", err)
		os.Exit(1)
	}
	fmt.Printf(string(output))
	client.Close()

}

func unmount(instruction Instruction) {
	connect(instruction.FPGAIP)

	// Open a new session
	session, err := client.NewSession()
	if err != nil {
		fmt.Println("Failed to create session:", err)
		os.Exit(1)
	}
	defer session.Close()

	// Run the command on the remote server
	output, err := session.CombinedOutput("/root/unmount.sh")
	if err != nil {
		fmt.Println("Failed to run command:", err)
		os.Exit(1)
	}
	fmt.Printf(string(output))

	client.Close()

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
	mount(instruction)

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
	unmount(instruction)

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
		return
	}

	for i := 0; i < len(FPGAs); i++ {
		if FPGAs[i].IP == state.FPGAIP {
			FPGAs[i].State = state.State
		}
	}

	c.JSON(http.StatusOK, state)
}