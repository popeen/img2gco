<?php
@session_start();
?>
<table>
    <tr id="headings">
		<td></td>
        <td>Input image</td>
        <td>Gcode</td>
    </tr>
    <tr id="previews">
		<td>
		
            <a href='index.php?do=rotate' class="button">↴ Rotate</a>
				<a href='index.php?do=flipH' class="button">↴ Flip Horizontal</a>
				<a href='index.php?do=flipV' class="button">↴ Flip Vertical</a>
		</td>
        <td>
            <?php
            $timestamp = filemtime($_SESSION["filename"] . ".png");
            echo "<img src='" . $_SESSION["filename"] . ".png?refresh=$timestamp' />";
            ?>
        </td>
        <td>
            <?php
            if (file_exists($_SESSION["filename"] . ".ngc")) {
                $timestamp = filemtime($_SESSION["filename"] . ".svg");
                echo "<img class='invalidate-on-param-change' src='" . $_SESSION["filename"] . ".svg?refresh=$timestamp' /><br/>";

                $durationSeconds = file_get_contents($_SESSION["filename"] . ".duration");
                $duration = sprintf("%02d:%02d:%02d", floor($durationSeconds/3600), ($durationSeconds/60)%60, $durationSeconds%60);
                echo "<span class='invalidate-on-param-change'>Estimated machining time: $duration</span><br/>";

                echo "<a class='button invalidate-on-param-change' href='" . $_SESSION["filename"] . ".ngc' target='_blank' download>✓ Download gcode</a>";
            } else {
                echo "No gcode generated yet.";
            }
            ?>
        </td>
    </tr>
    <tr id="buttons">
		<td></td>
        <td>
            <a href='index.php?do=clearImage' class="button">✗ Select other image</a>
        </td>
        <td>
            <a href='javascript:generate("reprap")' class="button">⚙ Generate reprap gcode</a>
            <a href='javascript:generate("grbl")' class="button">⚙ Generate grbl gcode</a>
        </td>
    </tr>
</table>
<br/>
<br/>
