//SMResourceManager.LoadScript("templates/_BaseGeneric/enhancements/menu.normal.js");

// Load relative to current path
var src = document.scripts[document.scripts.length - 1].src;
var path = src.substring(0, src.lastIndexOf("/"));
SMResourceManager.LoadScript(path + "/../../_BaseGeneric/enhancements/menu.normal.js");
