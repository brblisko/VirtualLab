package main

import (
	"net"
	"net/http"

	"github.com/gin-gonic/gin"
)

func instructionCreate(instruction Instruction) ErrorInternal {

	return ErrorInternal{ErrorCode: 0, Message: ""}
}

func instructionDelete(instruction Instruction) ErrorInternal {
	return ErrorInternal{ErrorCode: 0, Message: ""}
}

func checkIPsInstruction(instruction Instruction) ErrorInternal {
	if addr := net.ParseIP(instruction.FPGAIP); addr == nil {
		return ErrorInternal{ErrorCode: WRONG_IP, Message: "wrong FPGA IP address format"}
	}

	if addr := net.ParseIP(instruction.ClientIP); addr == nil {
		return ErrorInternal{ErrorCode: WRONG_IP, Message: "wrong Client IP address format"}
	}

	return ErrorInternal{ErrorCode: 0, Message: ""}
}

func parseInstruction(instruction Instruction) ErrorInternal {
	if instruction.Type == "CREATE" {
		if err := checkIPsInstruction(instruction); err.ErrorCode != OK {
			return err
		}

		if err := instructionCreate(instruction); err.ErrorCode != OK {
			return err
		}

		return ErrorInternal{ErrorCode: 0, Message: ""}
	}

	if instruction.Type == "DELETE" {
		if err := checkIPsInstruction(instruction); err.ErrorCode == OK {
			return err
		}

		if err := instructionDelete(instruction); err.ErrorCode != OK {
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
