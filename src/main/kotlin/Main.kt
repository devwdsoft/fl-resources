import com.example.util.ImageCrawlerUtil
import java.io.File
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date

//TIP To <b>Run</b> code, press <shortcut actionId="Run"/> or
// click the <icon src="AllIcons.Actions.Execute"/> icon in the gutter.
fun main() {
    val name = "Kotlin"

    val file = File("assets/test.txt")
    file.appendText("\n${SimpleDateFormat().format(Date())}")
    ImageCrawlerUtil().crawlImage("enet/8456.png")
}