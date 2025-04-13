package teams

import Constant
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import utils.ImageCrawlerUtil
import java.io.File

@Serializable
data class StaticImgTeam(val ID: String, val StaticImg: String)

object TeamImageCrawler {
    private const val teamImagePath = "assets/image/teams/"
    private const val teamStaticImagePath = "assets/static/teams/"

    private val json = Json { ignoreUnknownKeys = true }

    val staticImageTeams = mutableMapOf<String, String>()

    fun fetchTeamImages() {
        initData()
        val teams = TeamExtractor.fetchTeams(3)
            .plus(TeamExtractor.fetchTeams(4))
            .plus(TeamExtractor.fetchTeams(5))
            .filter { !it.Img.isNullOrBlank() }
            .distinctBy { it.ID }
        println("\uD83D\uDE80 \uD83D\uDE80 \uD83D\uDE80FETCHED ${teams.size} TEAMS FROM API")
        teams.forEach {
            crawTeamImage(it.ID, it.Nm, staticImageTeams[it.ID], it.Img.orEmpty())
        }
    }

    fun initData() {
        val staticImgJson = File("assets/config/static-map.json").readText()
        staticImageTeams.clear()
        staticImageTeams.putAll(json.decodeFromString<Map<String, String>>(staticImgJson).filter {
            it.value.isNotBlank()
        })
    }

    private fun crawTeamImage(ID: String, Nm: String, StaticImg: String?, Img: String) {
        val destinationPath = "$teamImagePath$Img"
        val destinationFile = File(destinationPath)

        // Copy from StaticImg if it's not null or empty
        if (!StaticImg.isNullOrBlank()) {
            val sourceFile = File("$teamStaticImagePath$StaticImg")
            if (sourceFile.exists()) {
                sourceFile.copyTo(destinationFile, overwrite = true)
                println("CRAW SUCCESS - STATIC :$ID $Nm $StaticImg to $Img")
                return
            } else {
                println("CRAW STATIC FAIL WITH NO STATIC FILE: $ID $Nm $StaticImg to $Img")
            }
        }
        // Try to crawl high-quality image
        val highQualityUrl = "${System.getenv(Constant.ENV_HIGH_QUALITY_URL)}$Img"
        val highQualitySuccess = ImageCrawlerUtil.crawlImage(highQualityUrl, destinationPath)
        if (highQualitySuccess) {
            println("CRAW SUCCESS - HIGH QUALITY: $ID $Nm $Img")
        } else {
            // If high-quality fails, try medium-quality
            val mediumQualityUrl = "${System.getenv(Constant.ENV_MEDIUM_QUALITY_URL)}$Img"
            val mediumQualitySuccess = ImageCrawlerUtil.crawlImage(mediumQualityUrl, destinationPath)
            if (mediumQualitySuccess) {
                println("CRAW SUCCESS - MEDIUM QUALITY: $ID $Nm $Img")
            } else {
                println("\uD83D\uDD25 \uD83D\uDD25 \uD83D\uDD25CRAW FAIL: $ID $Nm $Img")
            }
        }
    }
}
