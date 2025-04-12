package teams

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import utils.ImageCrawlerUtil
import java.io.File

@Serializable
data class StaticImgTeam(val ID: String, val StaticImg: String)

object TeamImageCrawler {
    private const val highQualityBaseUrl = "https://lsm-static-prod.lsmedia8.com/high/"
    private const val mediumQualityBaseUrl = "https://lsm-static-prod.lsmedia8.com/medium/"
    private const val teamImagePath = "assets/teams/"

    private val json = Json { ignoreUnknownKeys = true }

    val staticImageTeams = mutableMapOf<String, String>()

    fun initData() {
        val staticImgJson = File("assets/config/static-map.json").readText()
        staticImageTeams.clear()
        staticImageTeams.putAll(json.decodeFromString<Map<String, String>>(staticImgJson))
    }

    fun crawTeamImage(ID: String, StaticImg: String?, Img: String) {
        val destinationPath = "$teamImagePath$Img"
        val destinationFile = File(destinationPath)

        // Copy from StaticImg if it's not null or empty
        if (!StaticImg.isNullOrEmpty()) {
            val sourceFile = File("assets/$StaticImg")
            if(sourceFile.exists()) {
                sourceFile.copyTo(destinationFile, overwrite = true)                
                println("Copied from static image: $StaticImg to $Img")
                return
            }

        }
        // Try to crawl high-quality image
        val highQualityUrl = "$highQualityBaseUrl$Img"
        val highQualitySuccess = ImageCrawlerUtil.crawlImage(highQualityUrl, destinationPath)
        if (highQualitySuccess) {
            println("Crawled high quality image: $highQualityUrl")
        } else {
            // If high-quality fails, try medium-quality
            val mediumQualityUrl = "$mediumQualityBaseUrl$Img"
            val mediumQualitySuccess = ImageCrawlerUtil.crawlImage(mediumQualityUrl, destinationPath)
            if (mediumQualitySuccess) {
                println("Crawled medium quality image: $mediumQualityUrl")
            } else {
                println("Failed to crawl image for: $Img")
            }
            
        }
    }
}
