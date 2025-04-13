package teams

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import okhttp3.OkHttpClient
import okhttp3.Request
import java.time.LocalDate
import java.time.format.DateTimeFormatter

@Serializable
data class Team(val ID: String, val Nm: String, val Img: String? = null)

@Serializable
data class Event(val T1: List<Team> = emptyList(), val T2: List<Team> = emptyList())

@Serializable
data class Stage(val Events: List<Event> = emptyList())

@Serializable
data class Root(val Stages: List<Stage> = emptyList())

object TeamExtractor {

    private val client = OkHttpClient.Builder().build()
    private val json = Json { ignoreUnknownKeys = true }

    fun generateUrl(dayOffset: Int): String {
        val date = LocalDate.now().plusDays(dayOffset.toLong())
        val formatted = date.format(DateTimeFormatter.ofPattern("yyyyMMdd"))
        return "${System.getenv("BASE_SCHEDULE_API")}/$formatted/0?MD=0"
    }

    fun fetchTeams(dayOffset: Int): List<Team> {
        val url = generateUrl(dayOffset)
        val request = Request.Builder().url(url).build()
        client.newCall(request).execute().use { response ->
            if (!response.isSuccessful) error("Unexpected code $response")
            val body = response.body?.string() ?: return emptyList()
            val data = json.decodeFromString<Root>(body)
            return data.Stages.flatMap { stage ->
                stage.Events.flatMap { event ->
                    event.T1 + event.T2
                }
            }
        }
    }

    private fun fetchJsonFromUrl(url: String): String {
        val request = Request.Builder().url(url).build()
        client.newCall(request).execute().let { response ->
            if (!response.isSuccessful) {
                throw Exception("Failed to fetch data from $url. HTTP ${response.code}")
            }
            return response.body?.string() ?: throw Exception("Empty response body")
        }
        throw Exception("Failed to fetch data from $url")
    }
}