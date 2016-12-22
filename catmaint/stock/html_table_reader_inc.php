<?php

/*
 * Read a HTML table.
 * 
 * Reads the specified file, and returns an array of arrays
 * (jagged) contain the html rows and columns (row-major) in the file.
 * 
 * This works on <tr> and either <td> or <th> elements. The
 * contents of all other elements should be ignored.
 * 
 * Results on nested tables are undefined, nested tables should
 * not be used.
 * 
 * The document may contain more than one table, but they must not
 * be nested.
 * 
 */

function read_html_table($filename)
{
    $dom = new DOMDocument();
    $dom->loadHTMLFile($filename);
    $tr_elems = $dom->getElementsByTagName("tr");
    $rows = Array();
    foreach ($tr_elems as $tr) {
        # Get child elements, ignore anything which is not 
        # an element.
        $row = Array();
        foreach ($tr->childNodes as $node) {
            if ($node->nodeType == XML_ELEMENT_NODE) {
                $row [] = $node->textContent;
            }
        }
        $rows[] = $row;
    }
    return $rows;
}


?>
