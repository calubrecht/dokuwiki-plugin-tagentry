function tagentry_clicktag(tagname, cbox) {
  tagentry_settag(tagname,cbox.checked);
}
function tagentry_settag(tagname, on) {
  var oldtext = document.getElementById('wiki__text').value;
  var tagstart = oldtext.toLowerCase().indexOf("{{tag>");

  if (tagstart >= 0) {
    var tagend = oldtext.substr(tagstart).indexOf("}}");
    if (tagend < 0) {
      alert('incomlete "{{tag>}}" - missing trailing "}}" ?');
      return;
    }
    // remove this tag is already present
    var len = tagname.length+1; 
    var s=tagstart;
    var l=tagend;
    var f=-1;
    while ((f=oldtext.toLowerCase().substr(s,l).indexOf(tagname.toLowerCase())) >= 0) {
      var cs = oldtext.substr(s+f-1,1); // char before
      if (cs != ' ' && cs != '>' ) { s+=f+len; l-=f+len; continue; }
      var ce = oldtext.substr(s+f+len-1,1); // char after
      if (ce != ' ' && ce != '}' ) { s+=f+len; l-=f+len; continue; }
      if (ce == '}' ) {  // no trailing whitespace to remove
        if (cs == '>' ) { len--; } else { f--; }
      }
      oldtext = oldtext.substr(0,s+f)+oldtext.substr(s+f+len);
      l = oldtext.substr(s).indexOf("}}");
    }
    tagend = oldtext.substr(tagstart).indexOf("}}");

    if (tagend < 0) {
      alert('incomlete "{{tag>}}" - missing trailing "}}" ?');
      return;
    }

    if (on) { // insert tag 
      var split = tagstart+tagend;
      var ws ="";
      if (tagend != 6) ws=" "; // empty "{{tag>" - TODO remove it!?
      oldtext = oldtext.substr(0,split)+ws+tagname+oldtext.substr(split);
    }
  } else {
    if (on) { // insert tag 
      oldtext+='\n{{tag>'+tagname+'}}';
    }
  }

  document.getElementById('wiki__text').value = oldtext;
}
