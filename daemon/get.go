/**
 * Application    VirtualLab
 * Author         Boris Vesely
 */

package main

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

func getFPGAs(c *gin.Context) {
	c.JSON(http.StatusOK, FPGAs)
}

func getTunnels(c *gin.Context) {
	c.JSON(http.StatusOK, tunnels)
}
