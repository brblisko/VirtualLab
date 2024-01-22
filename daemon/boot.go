package main

import (
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"strconv"
)

func boot() {
	tablesFlush()
	if err != nil {
		return
	}

	FPGAs, err = loadFPGAs("./data/FPGAs.json")
	if err != nil {
		return
	}

	masqueradeCreate()
	if err != nil {
		return
	}
}

func saveJSON(filename string, data interface{}) error {
	// Convert data to JSON format
	jsonData, err := json.MarshalIndent(data, "", "  ")
	if err != nil {
		return err
	}

	// Write JSON data to the file
	err = os.WriteFile(filename, jsonData, 0644)
	if err != nil {
		return err
	}

	fmt.Printf("Data saved to %s\n", filename)
	return nil
}

func loadFPGAs(filename string) ([]FPGA, error) {
	// Read JSON data from the file
	jsonData, err := os.ReadFile(filename)
	if err != nil {
		return nil, err
	}

	// Unmarshal JSON data into the appropriate struct
	var temp []FPGA
	err = json.Unmarshal(jsonData, &temp)
	if err != nil {
		return nil, err
	}

	fmt.Printf("Data loaded from %s\n", filename)
	return temp, nil
}

func masqueradeCreate() {
	for i := 0; i < len(FPGAs); i++ {
		table := "nat"
		chain := "PREROUTING"
		protocol := "tcp"
		dport := strconv.Itoa(common.StartingPort + i)
		destination := FPGAs[i].IP + ":9090"

		ruleSpec := []string{"-p", protocol, "--dport", dport, "-j", "DNAT", "--to-destination", destination}

		err = ipt.Append(table, chain, ruleSpec...)
		if err != nil {
			return
		}
		FPGAs[i].Port = common.StartingPort + i
	}

	table := "nat"
	chain := "POSTROUTING"

	ruleSpec := []string{"-j", "MASQUERADE"}

	err = ipt.Append(table, chain, ruleSpec...)
	if err != nil {
		return
	}

	cmd := exec.Command("iptables", "-P", "FORWARD", "DROP")

	err := cmd.Run()
	if err != nil {
		return
	}

}

func tablesFlush() {
	// Flush all rules in the default table
	err = ipt.ClearChain("filter", "INPUT")
	if err != nil {
		return
	}

	err = ipt.ClearChain("filter", "FORWARD")
	if err != nil {
		return
	}

	err = ipt.ClearChain("filter", "OUTPUT")
	if err != nil {
		return
	}

	// Flush all rules in the nat table
	err = ipt.ClearChain("nat", "PREROUTING")
	if err != nil {
		return
	}

	// Flush and delete the user-defined chains
	err = ipt.ClearChain("nat", "POSTROUTING")
	if err != nil {
		return
	}

	// Flush and delete the user-defined chains
	err = ipt.ClearChain("nat", "OUTPUT")
	if err != nil {
		return
	}
}
