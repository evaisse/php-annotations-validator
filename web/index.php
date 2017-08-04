<?php

/**
 * Validate the syntax of a json-schema annotation
 * @author evaisse
 */

function uncompress($compressedString)
{
    $compressedString = (string) $compressedString;
    $compressedString = strtr($compressedString, '-_', '+/');
    $compressedString = base64_decode($compressedString . '=');
    $compressedString = stripslashes($compressedString);
    $string = gzuncompress($compressedString);
    return $string;
}

$content =<<<EVBUFFER_EOF
* @RequestSchema({
             *   "\$schema": "http://json-schema.org/draft-04/schema#",
             *   "title": "OrderBook",
             *   "description": Return the typical error.",
             *   "type": "object",
             *   "required": ["symbols"],
             *   "properties": {
             *     "offset": {"type": "integer", "description": "Offset of news list to retrieve", "default": 0},
             *     "\$commentLimit": {"type": "integer", "description": "Limit the number of comments for each news", "default": "0"},
             *     "fullContent": {"type": "boolean", "description": "If feeds given, allow to return or not the full articles text. If nums given, fullArticle is forced to ", "default": "0"},
             *   }
             * })
EVBUFFER_EOF;


if (!empty($_GET['content'])) {
    $content = $_GET['content'];
} else if (!empty($_GET['zcontent'])) {
    $content = uncompress($_GET['zcontent']);
}


?>

<head>
    <title>Annotations Validator</title>
    <link rel="stylesheet" href="/app.css">
    <style>
        .highlight {
            background-color: #ff9077;
            border-bottom: 1px solid red;
        }
        #docblock {
            line-height: 1rem;
            font-size: 11px;
            border: 1px solid #DDD;
            background: #EEE;
            padding: 5px;;
        }
    </style>
</head>

<body>


    <article>

        <h1>Annotations validator</h1>

        <pre id="output"></pre>
        <div contenteditable="true"
             id="docblock"
             class="monospace"
             style="white-space: pre;background: transparent"><?php echo htmlentities($content) ?></div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/acorn/4.0.11/acorn.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
        <script type="text/javascript" src="/app.js"></script>
    </article>
</body>