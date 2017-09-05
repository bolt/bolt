CodeMirror.defineMode("htmltwig", function(config, parserConfig) {
    return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text/html"), CodeMirror.getMode(config, "twig"));
});
