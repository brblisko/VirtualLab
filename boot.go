package main

import (
	"os/exec"
	"strconv"
)

func boot() {
	tablesFlush()
	if err != nil {
		return
	}

	masqueradeCreate()
	if err != nil {
		return
	}
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
