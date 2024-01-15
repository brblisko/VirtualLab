package main

import (
	"fmt"
	"os"

	"golang.org/x/crypto/ssh"
)

func connect(ip string) {
	// SSH key file path
	keyPath := "/home/boris/.ssh/id_rsa"

	// Destination server details
	username := "root"
	server := ip

	// Read the private key file
	key, err := os.ReadFile(keyPath)
	if err != nil {
		fmt.Println("Failed to read private key:", err)
		os.Exit(1)
	}

	// Parse the private key
	signer, err := ssh.ParsePrivateKey(key)
	if err != nil {
		fmt.Println("Failed to parse private key:", err)
		os.Exit(1)
	}

	// SSH client configuration
	config := &ssh.ClientConfig{
		User: username,
		Auth: []ssh.AuthMethod{
			ssh.PublicKeys(signer),
		},
		HostKeyCallback: ssh.InsecureIgnoreHostKey(),
	}

	// Connect to the SSH server
	client, err = ssh.Dial("tcp", server+":22", config)
	if err != nil {
		fmt.Println("Failed to connect to the server:", err)
		os.Exit(1)
	}

	fmt.Println("Successfully connected to %v.\n", ip)
}
