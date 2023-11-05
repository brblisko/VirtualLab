package main

type FPGA struct {
	IP    string `json:"ip"`
	State string `json:"state"`
}

type Tunnel struct {
	FPGAIP    string `json:"fpgaip"`
	ClientIP  string `json:"clientip"`
	Timestamp int64  `json:"timestamp"`
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
