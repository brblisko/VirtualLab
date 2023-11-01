package main

type FPGA struct {
	ID    string `json:"id"`
	IP    string `json:"ip"`
	State string `json:"state"`
}

type Tunnel struct {
	ID        string `json:"id"`
	FPGAIP    string `json:"fpgaip"`
	ClientIP  string `json:"clientip"`
	Timestamp int    `json:"timestamp"`
}

type Instruction struct {
	Type     string `json:"type"`
	FPGAIP   string `json:"fpgaip"`
	ClientIP string `json:"clientip"`
}

type ErrorInternal struct {
	ErrorCode int    `json:"errorcode"`
	Message   string `json:"message"`
}

const (
	OK           = 0
	WRONG_IP     = 1
	UNKNOWN_INST = 2
)
