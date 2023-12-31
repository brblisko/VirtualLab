package main

import (
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
)

func newTunnel(instruction Instruction) {
	var tmpTunnel = Tunnel{
		FPGAIP:    instruction.FPGAIP,
		ClientIP:  instruction.ClientIP,
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
	table := "filter"
	chain := "FORWARD"
	ruleSpec := []string{
		"-i", common.ClientInterface,
		"-o", common.PYNQInterface,
		"-s", instruction.ClientIP,
		"-d", instruction.FPGAIP + "/24",
		"-j", "ACCEPT",
	}

	err = ipt.Append(table, chain, ruleSpec...)
	if err != nil {
		return ErrorInternal{ErrorCode: 1, Message: err.Error()}
	}

	ruleSpec = []string{
		"-i", common.PYNQInterface,
		"-o", common.ClientInterface,
		"-s", instruction.FPGAIP + "/24",
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
	table := "filter"
	chain := "FORWARD"
	ruleSpec := []string{
		"-i", common.ClientInterface,
		"-o", common.PYNQInterface,
		"-s", instruction.ClientIP,
		"-d", instruction.FPGAIP + "/24",
		"-j", "ACCEPT",
	}

	err = ipt.Delete(table, chain, ruleSpec...)
	if err != nil {
		return ErrorInternal{ErrorCode: 1, Message: err.Error()}
	}

	ruleSpec = []string{
		"-i", common.PYNQInterface,
		"-o", common.ClientInterface,
		"-s", instruction.FPGAIP + "/24",
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
