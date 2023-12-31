package main

import (
	"fmt"

	"github.com/coreos/go-iptables/iptables"
	"github.com/gin-gonic/gin"
)

var FPGAs = []FPGA{
	{IP: "192.168.2.99", State: "DEFAULT"},
}

var ipt, err = iptables.New()

var tunnels = []Tunnel{}

var common = Commons{
	ClientInterface: "enp0s8",
	PYNQInterface:   "enp0s9",
	StartingPort:    20000,
}

func main() {
	if err != nil {
		fmt.Printf("Error creating iptables client: %v\n", err)
		return
	}

	boot()
	if err != nil {
		fmt.Printf("Error running boot commands: %v\n", err)
		return
	}

	router := gin.Default()
	router.GET("/FPGAs", getFPGAs)
	router.GET("/Tunnels", getTunnels)
	router.POST("/Instruction", postInstruction)

	router.Run("localhost:8080")
}
