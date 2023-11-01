package main

import (
	"github.com/gin-gonic/gin"
)

var FPGAs = []FPGA{
	{ID: "1", IP: "10.0.2.4", State: "DEFAULT"},
}

var Tunnels = []Tunnel{}

func main() {
	router := gin.Default()
	router.GET("/FPGAs", getFPGAs)
	router.GET("/Tunnels", getTunnels)
	router.POST("/Instruction", postInstruction)

	router.Run("localhost:8080")
}
