package com.example.util

import java.io.File
import java.net.URL
import java.nio.file.Files
import java.nio.file.Paths
import java.nio.file.StandardCopyOption

class ImageCrawlerUtil {

    companion object {
        private const val highQualityBaseUrl = "https://lsm-static-prod.lsmedia8.com/high/"
        private const val mediumQualityBaseUrl = "https://lsm-static-prod.lsmedia8.com/medium/"
    }

    var filePath: String = ""

    fun crawlImage(filePath: String) {
        this.filePath = filePath
        var success = false
        try {
            success = downloadImage(highQualityBaseUrl)
        } catch (e: Exception) {
            println("Failed to download from high quality URL: ${e.message}")
        }

        if (!success) {
            try {
                success = downloadImage(mediumQualityBaseUrl)
            } catch (e: Exception) {
                println("Failed to download from medium quality URL: ${e.message}")
            }
        }

        if (!success) {
            println("Failed to download image from both URLs for $filePath")
        }
    }

    private fun downloadImage(baseUrl: String): Boolean {
        val fullUrl = "$baseUrl$filePath"
        val destinationPath = "assets/images/teams/$filePath"
        val destinationFile = File(destinationPath)
        try {
            val parent = destinationFile.parentFile
            if (!parent.exists()) {
                parent.mkdirs()
            }
            val url = URL(fullUrl)
            val inputStream = url.openStream()
            Files.copy(inputStream, Paths.get(destinationPath), StandardCopyOption.REPLACE_EXISTING)
            return true
        } catch (e: Exception) {
            println("Failed to download from $baseUrl: ${e.message}")
            return false
        }
    }
}