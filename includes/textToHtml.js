function convertTextToHtml(text) {
    let noBreaks = text;
    let linebs = "<br />";
    noBreaks = noBreaks.replace(/\r\n/g, "XiLBXZ");
    noBreaks = noBreaks.replace(/\n/g, "XiLBXZ");
    noBreaks = noBreaks.replace(/\r/g, "XiLBXZ");
    let re4 = /XiLBXZXiLBXZ/gi;
    noBreaks = noBreaks.replace(re4, "</p><p>");
    re5 = /XiLBXZ/gi;
    noBreaks = noBreaks.replace(re5, linebs + "\r\n");
    noBreaks = "<p>" + noBreaks + "</p>";
    noBreaks = noBreaks.replace("<p></p>", "");
    noBreaks = noBreaks.replace("\r\n\r\n", "");
    noBreaks = noBreaks.replace(/<\/p><p>/g, "</p>\r\n\r\n<p>");
    noBreaks = noBreaks.replace(new RegExp("<p><br />", "g"), "<p>");
    noBreaks = noBreaks.replace(new RegExp("<p><br>", "g"), "<p>");
    return noBreaks;
}