Element.implement({
    getPoint: function(hPos, vPos) {
		var point={x:0, y:0}, size=this.getSize();
		if(window.debugConsole)
			debugConsole.notice('getPoint(hPos:'+hPos+', vPos:'+vPos+') : (id:'+this.get('id')+', size:('+size.x+','+size.y+'))\n');
		switch(hPos)
			{
			case 'left':
				point.x=0+(this.getStyle('border-left-width').toInt()>0?this.getStyle('border-left-width').toInt():0);
				break;
			case 'center':
				point.x=size.x/2;
				break;
			case 'right':
				point.x=size.x-(this.getStyle('border-right-width').toInt()>0?this.getStyle('border-right-width').toInt():0);
				break;
			default:
				point.x=(hPos?hPos:0);
				break;
			}
		switch(vPos)
			{
			case 'top':
				point.y=0+(this.getStyle('border-top-width').toInt()>0?this.getStyle('border-top-width').toInt():0);
				break;
			case 'center':
				point.y=size.y/2;
				break;
			case 'bottom':
				point.y=size.y-(this.getStyle('border-bottom-width').toInt()>0?this.getStyle('border-bottom-width').toInt():0);
				break;
			default:
				point.y=(vPos?vPos:0);
				break;
			}
		if(window.debugConsole)
			debugConsole.notice('point:'+point.x+','+point.y+'\n');
		return point;
    },
    hitTest: function(aElement) {
		var ePos=this.getAbsolutePosition();
		var eSize=this.getSize();
		var aPos=aElement.getAbsolutePosition();
		var aSize=aElement.getSize();
		if(window.debugConsole)
			debugConsole.notice('hitTest:x(aPos='+aPos.x+'+'+aSize.x+',ePos='+ePos.x+'+'+eSize.x+'),y(aPos='+aPos.y+'+'+aSize.y+',ePos='+ePos.y+'+'+eSize.y+')\n');
	   if((ePos.x >= aPos.x + aSize.x)
		|| (ePos.x + eSize.x <= aPos.x)
		|| (ePos.y >= aPos.y + aSize.y)
		|| (ePos.y + eSize.y <= aPos.y))
			  return false; 
	   else
			  return true;
		},
    getAbsolutePosition: function() {
		var point={x:0, y:0}, element=this;
		while ( element.nodeName != "BODY" ) {
			point.x += parseInt(element.offsetLeft);
			point.y += parseInt(element.offsetTop);
			element = (element.offsetParent?element.offsetParent:document.body);
			}
		return point;
    },
    getAnchoredPosition: function(aElement, params) {
		if(!params)
			params={aHPos:0,aVPos:0,hPos:0,vPos:0};
		var aPos=aElement.getAbsolutePosition();
		var aPoint=aElement.getPoint(params.aHPos,params.aVPos);
		var ePoint=this.getPoint(params.hPos,params.vPos);
		var newPos={x:0,y:0};
		newPos.x=aPos.x+aPoint.x-ePoint.x;
		newPos.y=aPos.y+aPoint.y-ePoint.y;
		return newPos;
    },
    setAnchoredPosition: function(aElement, params) {
		if(!params)
			params={aHPos:0,aVPos:0,hPos:0,vPos:0,noScroll:false,noVScroll:false,noHScroll:false,doNotCover:false};
		var parentPos=(this.offsetParent?this.offsetParent:document.body).getAbsolutePosition();
		var newPos=this.getAnchoredPosition(aElement,params);
		if(params.resizeInsteadScroll)
			{
			params.resizeInsteadHScroll=true;
			params.resizeInsteadVScroll=true;
			}
		if(params.resizeInsteadHScroll)
			params.noHScroll=true;
		if(params.resizeInsteadVScroll)
			params.noVScroll=true;
		if(params.noScroll||params.resizeInsteadScroll)
			{
			params.noHScroll=true;
			params.noVScroll=true;
			}
		else if(params.noMoreScroll)
			{
			params.noMoreHScroll=true;
			params.noMoreVScroll=true;
			}
		if(params.noVScroll||params.noHScroll||params.noMoreVScroll||params.noMoreHScroll||params.resizeInsteadVScroll||params.resizeInsteadHScroll)
			{
			var docScroll=window.getScroll();
			var docScrollSize=window.getScrollSize();
			var docSize=window.getSize();
			var eSize=this.getSize();
			if(params.resizeInsteadVScroll||params.noVScroll||params.noMoreVScroll)
				{
				if(params.resizeInsteadVScroll&&eSize.y>docSize.y)
					{
					this.setSize(docSize);
					newPos={x:0,y:0}
					}
				else if(eSize.y<docSize.y&&newPos.y+eSize.y>docScroll.y+docSize.y)
					{
					if(params.noVScroll)
						newPos.y=docScroll.y+docSize.y-eSize.y;
					else if(params.noMoreVScroll&&eSize.y<docScrollSize.y&&newPos.y+eSize.y>docScrollSize.y-docScroll.y)
						{
						newPos.y=docScrollSize.y-eSize.y;
						}
					}
				}
			if(params.resizeInsteadHScroll||params.noHScroll||params.noMoreHScroll)
				{
				if(params.resizeInsteadHScroll&&eSize.x>docSize.x)
					{
					this.setSize(docSize);
					newPos={x:0,y:0}
					}
				else if(eSize.x<docSize.x&&newPos.x+eSize.x>docScroll.x+docSize.x)
					{
					if(params.noHScroll)
						newPos.x=docScroll.x+docSize.x-eSize.x;
					else if(params.noMoreHScroll&&eSize.x<docScrollSize.x&&newPos.x+eSize.x>docScrollSize.x-docScroll.x)
						{
						newPos.x=docScrollSize.x-eSize.x;
						}
					}
				}
			}
		newPos.x-=parentPos.x;
		newPos.y-=parentPos.y;
		this.setPosition(newPos);
		/*if(params.doNotCover)
			{
			// not implemented
			}*/
		}
});