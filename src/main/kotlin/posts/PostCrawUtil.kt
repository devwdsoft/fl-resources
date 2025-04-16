package posts


import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import okhttp3.OkHttpClient
import okhttp3.Request

// Post đơn giản chứa id và title
data class Post(
    val id: String,
    val title: String
)

// Model để map JSON trả về từ API
@Serializable
data class Article(
    val id: String,
    val title: String
)

@Serializable
data class PageProps(
    val articles: List<Article>
)

@Serializable
data class NewsResponse(
    val pageProps: PageProps
)

object PostCrawUtil {

    private val client = OkHttpClient.Builder().build()
    private val json = Json { ignoreUnknownKeys = true }
    private lateinit var buildId: String

    fun initBuildId() {
        val request = Request.Builder().url("https://www.livescore.com/en/").build()
        val document = client.newCall(request).execute().body?.string()
        buildId = document?.split("/_buildManifest.js")?.first()?.split("/")?.last()!!
        println(buildId)
    }

    fun fetchPosts(): List<Post> {
        val apiUrl = "https://abc.com/news" // Thay bằng URL thật
        val request = Request.Builder().url(apiUrl).build()
        client.newCall(request).execute().use { response ->
            val body = response.body?.string() ?: return emptyList()
            val newsResponse = json.decodeFromString<NewsResponse>(body)
            return newsResponse.pageProps.articles.map { article ->
                Post(id = article.id, title = article.title)
            }
        }
    }
}
