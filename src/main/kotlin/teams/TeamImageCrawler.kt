package teams

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import java.io.File

@Serializable
data class StaticImgTeam(val ID: String, val StaticImg: String)

object TeamImageCrawler {
    private const val highQualityBaseUrl = "https://lsm-static-prod.lsmedia8.com/high/"
    private const val mediumQualityBaseUrl = "https://lsm-static-prod.lsmedia8.com/medium/"

    private val json = Json { ignoreUnknownKeys = true }

    val staticImageTeams = mutableMapOf<String, String>()

    fun initData() {
        val staticImgJson = File("assets/config/static-map.json").readText()
        staticImageTeams.clear()
        staticImageTeams.putAll(json.decodeFromString<Map<String, String>>(staticImgJson))
    }

    fun crawTeamImage(ID: String, StaticImg: String?, Img: String) {

    }
}

