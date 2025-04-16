package teams

import Constant
import extension.getEnv
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
        val teams = buildList {
            (0 until (getEnv(Constant.ENV_DATE_COUNT_TO_FETCH_TEAMS)?.toInt() ?: 1)).forEach {
                addAll(TeamExtractor.fetchTeams(it))
            }
        }.filter { !it.Img.isNullOrBlank() }
            .distinctBy { it.ID }
        teams.forEachIndexed { index, team ->
            crawTeamImage(
                team.ID,
                team.Nm,
                staticImageTeams[team.ID],
                team.Img.orEmpty(),
                String.format("%04d/%04d", index + 1, teams.size)
            )
        }
    }

    fun initData() {
        val staticImgJson = File("assets/config/static-map.json").readText()
        staticImageTeams.clear()
        staticImageTeams.putAll(json.decodeFromString<Map<String, String>>(staticImgJson).filter {
            it.value.isNotBlank()
        })
    }

    private fun crawTeamImage(ID: String, Nm: String, StaticImg: String?, Img: String, index: String) {
        val destinationPath = "$teamImagePath$Img"
        val destinationFile = File(destinationPath)

        // Copy from StaticImg if it's not null or empty
        if (!StaticImg.isNullOrBlank()) {
            val sourceFile = File("$teamStaticImagePath$StaticImg")
            if (sourceFile.exists()) {
                sourceFile.copyTo(destinationFile, overwrite = true)
                println("$index CRAW SUCCESS - STATIC :$ID $Nm $StaticImg to $Img")
                return
            } else {
                println("$index CRAW STATIC FAIL WITH NO STATIC FILE: $ID $Nm $StaticImg to $Img")
            }
        }
        // Try to crawl high-quality image
        val highQualityUrl = "${getEnv(Constant.ENV_HIGH_QUALITY_URL)}$Img"
        val highQualitySuccess = ImageCrawlerUtil.crawlImage(highQualityUrl, destinationPath)
        if (highQualitySuccess) {
            println("$index CRAW SUCCESS - HIGH QUALITY: $ID $Nm $Img")
        } else {
            // If high-quality fails, try medium-quality
            val mediumQualityUrl = "${getEnv(Constant.ENV_MEDIUM_QUALITY_URL)}$Img"
            val mediumQualitySuccess = ImageCrawlerUtil.crawlImage(mediumQualityUrl, destinationPath)
            if (mediumQualitySuccess) {
                println("$index CRAW SUCCESS - MEDIUM QUALITY: $ID $Nm $Img")
            } else {
                println("$index \uD83D\uDD25 \uD83D\uDD25 \uD83D\uDD25CRAW FAIL: $ID $Nm $Img")
            }
        }
    }
}
