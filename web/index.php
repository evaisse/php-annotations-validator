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
             *   "description": Return the order book of the given symbol.",
             *   "type": "object",
             *   "required": ["symbols"],
             *   "properties": {
             *     "symbols": {"type": "string|array", "description": "(Single|Array of) Symbol resource ID", "example": "1rPGLE"},
             *     "offset": {"type": "integer", "description": "Offset of news list to retrieve", "default": 0},
             *     "limit": {"type": "integer", "description": "Limit the number of news", "default": "self::NB_PAR_PAGE"},
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
    <link rel="stylesheet" href="/_develtools/develtools.css">
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

        <script>
        function SelectText(element, line, col) {
            var doc = document,
                text = doc.getElementById(element),
                range,
                selection;

            if (doc.body.createTextRange) {
                range = document.body.createTextRange();
                range.moveToElementText(text);
                range.select();
            } else if (window.getSelection) {
                selection = window.getSelection();
                range = document.createRange();
                range.selectNodeContents(text);
                selection.removeAllRanges();
                selection.addRange(range);
            }
        }

        function getCaretPosition(editableDiv) {
            window.selOffsets = getSelectionCharacterOffsetWithin(editableDiv);
            console.log("Selection offsets: " + selOffsets.start + ", " + selOffsets.end);
        }
        function getTextNodesIn(node) {
            var textNodes = [];
            if (node.nodeType == 3) {
                textNodes.push(node);
            } else {
                var children = node.childNodes;
                for (var i = 0, len = children.length; i < len; ++i) {
                    textNodes.push.apply(textNodes, getTextNodesIn(children[i]));
                }
            }
            return textNodes;
        }

        function setSelectionRange(el, start, end) {
            if (document.createRange && window.getSelection) {
                var range = document.createRange();
                range.selectNodeContents(el);
                var textNodes = getTextNodesIn(el);
                var foundStart = false;
                var charCount = 0, endCharCount;

                for (var i = 0, textNode; textNode = textNodes[i++]; ) {
                    endCharCount = charCount + textNode.length;
                    if (!foundStart && start >= charCount
                        && (start < endCharCount ||
                        (start == endCharCount && i <= textNodes.length))) {
                        range.setStart(textNode, start - charCount);
                        foundStart = true;
                    }
                    if (foundStart && end <= endCharCount) {
                        range.setEnd(textNode, end - charCount);
                        break;
                    }
                    charCount = endCharCount;
                }

                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            } else if (document.selection && document.body.createTextRange) {
                var textRange = document.body.createTextRange();
                textRange.moveToElementText(el);
                textRange.collapse(true);
                textRange.moveEnd("character", end);
                textRange.moveStart("character", start);
                textRange.select();
            }
        }
        function getSelectionCharacterOffsetWithin(element) {
            var start = 0;
            var end = 0;
            var doc = element.ownerDocument || element.document;
            var win = doc.defaultView || doc.parentWindow;
            var sel;
            if (typeof win.getSelection != "undefined") {
                sel = win.getSelection();
                if (sel.rangeCount > 0) {
                    var range = win.getSelection().getRangeAt(0);
                    var preCaretRange = range.cloneRange();
                    preCaretRange.selectNodeContents(element);
                    preCaretRange.setEnd(range.startContainer, range.startOffset);
                    start = preCaretRange.toString().length;
                    preCaretRange.setEnd(range.endContainer, range.endOffset);
                    end = preCaretRange.toString().length;
                }
            } else if ( (sel = doc.selection) && sel.type != "Control") {
                var textRange = sel.createRange();
                var preCaretTextRange = doc.body.createTextRange();
                preCaretTextRange.moveToElementText(element);
                preCaretTextRange.setEndPoint("EndToStart", textRange);
                start = preCaretTextRange.text.length;
                preCaretTextRange.setEndPoint("EndToEnd", textRange);
                end = preCaretTextRange.text.length;
            }
            return { start: start, end: end };
        }


        window.onload = function() {
            document.addEventListener("selectionchange", reportSelection, false);
            document.addEventListener("mouseup", reportSelection, false);
            document.addEventListener("mousedown", reportSelection, false);
            document.addEventListener("keyup", reportSelection, false);
        };


    (function () {
        var input = document.getElementById('docblock');
        var output = document.getElementById('output');
        var previousPos = 0;

        input.addEventListener('input', function (e) {
            previousPos = getCaretPosition(input);
            validate();
        });

        var validate = _.debounce(function () {
            console.log('validate');
            var e;
            var res = input.innerText
                .replace(/^\s*(\*)\s?/gm, function (r) {
                    return r.replace('*', " ");
                })
                .replace(/^\s*(\@[a-z0-9]+)\(/gmi, function (r) {
                    return r.replace('@', "A");
                });


            previousPos = getCaretPosition(input) ? getCaretPosition(input) : previousPos;

            try {
                acorn.parse(res);
                output.className = "success";
                output.innerHTML = "ok";
            } catch (e) {
                var start = Math.max(e.pos - 7, 0);
                var end = Math.min(e.pos + 7, res.length);
                output.className = "error";
                output.innerHTML = e;
                input.innerHTML = input.innerText.substr(0,start) + '<span class="highlight">' + input.innerText.substr(start, end - start) + '</span>' + input.innerText.substr(end);
            }

            var schema = eval(res);

            console.log(schema);



            console.log(previousPos);
            setTimeout(function () {
                setSelectionRange(input, selOffsets.start, selOffsets.end);
            }, 50);

        }, 200);

        if (input.innerHTML.length) {
            validate();
        }

    })();
    </script>
    </article>
</body>