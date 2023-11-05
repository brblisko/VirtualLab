package main

import (
	"fmt"

	"github.com/coreos/go-iptables/iptables"
	"github.com/gin-gonic/gin"
)

var FPGAs = []FPGA{
	{IP: "10.0.2.4", State: "DEFAULT"},
}

var ipt, err = iptables.New()

var tunnels = []Tunnel{}

func main() {
	if err != nil {
		fmt.Printf("Error creating iptables client: %v\n", err)
		return
	}

	router := gin.Default()
	router.GET("/FPGAs", getFPGAs)
	router.GET("/Tunnels", getTunnels)
	router.POST("/Instruction", postInstruction)

	router.Run("localhost:8080")
}
