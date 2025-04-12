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

    private val staticImageTeams = mutableMapOf<String, String>()

    fun fetchTeamImages() {
        initData()
        val teams = TeamExtractor.fetchTeams(0)
            .plus(TeamExtractor.fetchTeams(1))
            .plus(TeamExtractor.fetchTeams(2))
            .filter { !it.Img.isNullOrBlank() }
            .distinctBy { it.ID }
        println("FETCHED ${teams.size} TEAMS FROM API")
        teams.forEach {
            crawTeamImage(it.ID, it.Nm, staticImageTeams[it.ID], it.Img.orEmpty())
        }
    }

    private fun initData() {
        val staticImgJson = File("assets/config/static-map.json").readText()
        staticImageTeams.clear()
        staticImageTeams.putAll(json.decodeFromString<Map<String, String>>(staticImgJson))
    }

    private fun crawTeamImage(ID: String, Nm: String, StaticImg: String?, Img: String) {
        val destinationPath = "$teamImagePath$Img"
        val destinationFile = File(destinationPath)

        // Copy from StaticImg if it's not null or empty
        if (!StaticImg.isNullOrBlank()) {
            val sourceFile = File("assets/$StaticImg")
            if (sourceFile.exists()) {
                sourceFile.copyTo(destinationFile, overwrite = true)
                println("CRAW SUCCESS - STATIC :$ID $Nm $StaticImg to $Img")
                return
            } else {
                println("CRAW STATIC FAIL WITH NO STATIC FILE: $ID $Nm $StaticImg to $Img")
            }
        }
        // Try to crawl high-quality image
        val highQualityUrl = "$highQualityBaseUrl$Img"
        val highQualitySuccess = ImageCrawlerUtil.crawlImage(highQualityUrl, destinationPath)
        if (highQualitySuccess) {
            println("CRAW SUCCESS - HIGH QUALITY: $ID $Nm $Img")
        } else {
            // If high-quality fails, try medium-quality
            val mediumQualityUrl = "$mediumQualityBaseUrl$Img"
            val mediumQualitySuccess = ImageCrawlerUtil.crawlImage(mediumQualityUrl, destinationPath)
            if (mediumQualitySuccess) {
                println("CRAW SUCCESS - MEDIUM QUALITY: $ID $Nm $Img")
            } else {
                println("CRAW FAIL: $ID $Nm $Img")
            }
        }
    }
}
