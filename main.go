package main

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

type FPGA struct {
	ID        string `json:"id"`
	IP        string `json:"ip"`
	State     string `json:"state"`
	Timestamp string `json:"timestamp"`
	Action    string `json:"aciton"`
	IPTunnel  string `json:"iptunnel"`
}

var FPGAs = []FPGA{
	{ID: "1", IP: "10.0.2.4", State: "DEFAULT", Timestamp: "", Action: "", IPTunnel: ""},
}

func getFPGAs(c *gin.Context) {
	c.IndentedJSON(http.StatusOK, FPGAs)
}

func main() {
	router := gin.Default()
	router.GET("/FPGAs", getFPGAs)

	router.Run("localhost:8080")
}
