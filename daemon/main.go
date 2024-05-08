package main

import (
	"fmt"

	"github.com/coreos/go-iptables/iptables"
	"github.com/gin-gonic/gin"
	"golang.org/x/crypto/ssh"
)

var FPGAs = []FPGA{}

var ipt, err = iptables.New()

var tunnels = []Tunnel{}

var common = Commons{
	ClientInterface: "enp0s3",
	PYNQInterface:   "enp0s9",
	StartingPort:    30000,
}

var client *ssh.Client

// UserData directory
var userDataDir = "/home/student/UserData/"
// SSH key file path
var keyPath = "/home/boris/.ssh/id_rsa"

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
	router.POST("/State", postState)

	router.Run("localhost:20000")
}
