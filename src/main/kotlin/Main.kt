import kotlinx.serialization.Serializable
import teams.TeamExtractor

//TIP To <b>Run</b> code, press <shortcut actionId="Run"/> or
// click the <icon src="AllIcons.Actions.Execute"/> icon in the gutter.
fun main() {
    val name = "Kotlin"

//    val file = File("assets/test.txt")
//    file.appendText("\n${SimpleDateFormat().format(Date())}")
//    ImageCrawlerUtil().crawlImage("enet/8456.png")
//    ImageCrawlerUtil().crawlImage("enet/84526.png")
    println(
        TeamExtractor.fetchTeams(0).plus(TeamExtractor.fetchTeams(1)).toString()
    )
}

@Serializable
data class ImageEntry(val ID: String, val StaticImg: String)