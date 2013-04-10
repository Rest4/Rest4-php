var BeloteWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.debug=false;
		this.options.speed=2;
		this.classNames.push('BeloteWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-close',this.closeWindow.bind(this));
		this.app.registerCommand('win'+this.id+'-play',this.play.bind(this));
		this.app.registerCommand('win'+this.id+'-player',this.player.bind(this));
		this.app.registerCommand('win'+this.id+'-bid',this.bid.bind(this));
		this.app.registerCommand('win'+this.id+'-switchMode',this.switchMode.bind(this));
		this.app.registerCommand('win'+this.id+'-setSpeed',this.setSpeed.bind(this));
		this.app.registerCommand('win'+this.id+'-newGame',this.newGame.bind(this));
	},
	// Rendering window
	render : function() {
		// Menu
		this.options.menu.push({'label':this.locale.menu_mode,'title':this.locale.menu_mode_tx,'childs':[
				{'label':this.locale.menu_mode_normal,'command':'switchMode:normal','title':this.locale.menu_mode_normal_tx},
				{'label':this.locale.menu_mode_debug,'command':'switchMode:debug','title':this.locale.menu_mode_debug_tx}
			]});
		this.options.menu.push({'label':this.locale.menu_speed,'title':this.locale.menu_speed_tx,'childs':[
				{'label':this.locale.menu_speed_slow,'command':'setSpeed:3','title':this.locale.menu_speed_slow_tx},
				{'label':this.locale.menu_speed_normal,'command':'setSpeed:2','title':this.locale.menu_speed_normal_tx},
				{'label':this.locale.menu_speed_hight,'command':'setSpeed:1','title':this.locale.menu_speed_hight_tx},
				{'label':this.locale.menu_speed_max,'command':'setSpeed:0','title':this.locale.menu_speed_max_tx}
			]});
		// Unmodifiable options
		this.options.bottomToolbox=true;
		// Adding default contents
		if(!this.options.content)
			this.options.content=this.locale.content;
		// Drawing window
		this.parent();
	},
	// Rendering content
	renderContent: function(event)
		{
		this.game=new Game(this.view,'/',this);
		},
	// New game
	newGame: function(event)
		{
		if(!this.game.players)
			this.game.prepare();
		else
			{
			if(this.delayedCallback)
				clearTimeout(this.delayedCallback);
			this.game.start();
			}
		},
	// New game
	setSpeed: function(event, params)
		{
		this.options.speed=parseInt(params[0]);
		},
	// Switch mode
	switchMode: function(event, params)
		{
		this.options.debug=(params&&params[0]=='debug'?true:false);
		if(this.options.debug)
			this.game.gameLog.setStyle('display','');
		else
			this.game.gameLog.setStyle('display','none');
		this.update();
		},
	// Bid
	bid: function(event, params)
		{
		this.game.players[params[0]].bidCallback(params[1]);
		},
	// Play a card
	play: function(event, params)
		{
		this.game.players[params[0]].playCallback(params[1]);
		},
	// Play a card
	player: function(event, params)
		{
		this.game.players[params[0]].introduceMyself();
		},
	// Close
	closeWindow: function(event)
		{
		this.close();
		this.fireEvent('validate', [event, this.options.output]);
		},
	// handle win resize
	update: function()
		{
		this.parent();
		if(this.game)
			this.game.updatePopup();
		},
	// Window destruction
	destruct : function() {
		this.parent();
		}
});

var Game=new Class({
	initialize: function(element,rootPath,w) {
		this.displayWindow=w;
		// Props
		this.cards=new Array(
			{'name':'7','symbol':'7','suitRank':1,'suitVal':0,'trumpRank':1,'trumpVal':0,'column':6},
			{'name':'8','symbol':'8','suitRank':2,'suitVal':0,'trumpRank':2,'trumpVal':0,'column':7},
			{'name':'9','symbol':'9','suitRank':3,'suitVal':0,'trumpRank':7,'trumpVal':14,'column':8},
			{'name':'10','symbol':'10','suitRank':7,'suitVal':10,'trumpRank':5,'trumpVal':10,'column':9},
			{'name':'Jack','symbol':'J','suitRank':4,'suitVal':2,'trumpRank':8,'trumpVal':20,'column':10},
			{'name':'Queen','symbol':'Q','suitRank':5,'suitVal':3,'trumpRank':3,'trumpVal':3,'column':11},
			{'name':'King','symbol':'K','suitRank':6,'suitVal':4,'trumpRank':4,'trumpVal':4,'column':12},
			{'name':'Ace','symbol':'A','suitRank':8,'suitVal':11,'trumpRank':6,'trumpVal':11,'column':0});
		this.suits=new Array({'name':'diamonds','symbol':'&#9826;','color':'red','row':1},
			{'name':'clubs','symbol':'&#9827;','color':'black','row':0},
			{'name':'hearts','symbol':'&#9825;','color':'red','row':2},
			{'name':'spades','symbol':'&#9824;','color':'black','row':3});
		// Drawing game screen
		this.cardWidth=(w.app.screenType=='small'?50:100);
		this.cardHeight=(w.app.screenType=='small'?55:110);
		this.cardHeight=(w.app.screenType=='small'?72:144);
		this.cardMargin=(w.app.screenType=='small'?2:5);
		this.gameView=document.createElement('div');
		this.gameView.setAttribute('class','hbox');
		this.gameLog=document.createElement('div');
		this.gameLog.setAttribute('class','box');
		this.gameLog.setAttribute('style','overflow-y:scroll; max-width:400px;'+(w.options.debug?'':' display:none;'));
		this.gameZone=document.createElement('div');
		this.gameZone.setAttribute('class','box');
		var hHandDecal=Math.round(3*this.cardWidth/4);
		var hHandWidth=this.cardWidth+Math.round(7*this.cardWidth/4)+30;
		var hHandHeight=this.cardHeight+15;
		var vHandDecalRatio=(w.app.screenType=='small'?(1/6):(1/4));
		var vHandDecal=Math.round(this.cardHeight-(vHandDecalRatio*this.cardHeight));
		var vHandWidth=this.cardWidth;
		var vHandHeight=this.cardHeight+(7*Math.round(vHandDecalRatio*this.cardHeight))+15;
		this.gameZone.setAttribute('style','min-height:'+(vHandHeight+(hHandHeight*2))+'; min-width:'+(hHandWidth+(vHandWidth*2))+';'
		+' background-image: linear-gradient(bottom, rgb(73,118,41) 29%, rgb(103,154,70) 65%, rgb(134,185,98) 83%);'
		+' background-image: -o-linear-gradient(bottom, rgb(73,118,41) 29%, rgb(103,154,70) 65%, rgb(134,185,98) 83%);'
		+' background-image: -moz-linear-gradient(bottom, rgb(73,118,41) 29%, rgb(103,154,70) 65%, rgb(134,185,98) 83%);'
		+' background-image: -webkit-linear-gradient(bottom, rgb(73,118,41) 29%, rgb(103,154,70) 65%, rgb(134,185,98) 83%);'
		+' background-image: -ms-linear-gradient(bottom, rgb(73,118,41) 29%, rgb(103,154,70) 65%, rgb(134,185,98) 83%);');
		//+' zoom:'+(w.app.screenType=='small'?'0.5':'1')+';');
		this.gameZone.innerHTML='<style>'
			+'	.popup, .notice { position:absolute; background:rgba(0,0,0,0.8); display:none; padding:'+(w.app.screenType=='small'?'10':'50')+'px; color:#ddd; text-shadow: 2px 2px 8px #000, 1px 1px 0 #000,-1px -1px 0 #ccc; font-weight:bold; font-size:14px; border-radius:15px; text-align:center; min-width:'+(w.app.screenType=='small'?'100':'300')+'px; }\n'
			+'	.popup.active, .notice.active { display:block; }\n'
			+'	div.box .popup.active h2 { line-height:30px; font-size:28px; text-shadow: 2px 2px 8px #000; color:#ffcc00; text-shadow: 2px 2px 8px #000, 1px 1px 0 #ff3300,-1px -1px 0 #ffff00; text-align:center; margin:0 0 15px 0; font-weight:bold; font-family:Arial black, sans-serif; }\n'
			+'	div.box .popup.active p { margin:0 0 15px 0; }\n'
			+'	div.box .popup.active label { color:#ffcc00; text-shadow: 2px 2px 8px #000, 1px 1px 0 #ff3300,-1px -1px 0 #ffff00; line-height:30px; font-size:18px; }\n'
			+'	div.box .popup.active input[type="text"] { padding:0 10px; margin:0 0 0 15px; line-height:30px; border:0; border-radius:5px; box-shadow:1px 1px 1px #000; }\n'
			+'	div.box .popup.active input[type="submit"] { padding:0 10px; line-height:30px; height:30px; border:0; border-radius:5px; box-shadow:1px 1px 1px #000, 1px 1px 0 #ff3300,-1px -1px 0 #ffff00; font-weight:bold; color:#ffcc00; text-shadow: 2px 2px 15px #ff3300, 1px 1px 0 #ff3300,-1px -1px 0 #ffff00; background:#ffdd00; }\n'
			+'	.card { display:block; width:'+this.cardWidth+'px; height:'+this.cardHeight+'px; box-shadow:1px 1px 0px #666; background:url(/mpfs/public/images/cards_faces'+(w.app.screenType=='small'?'_small':'')+'.png) no-repeat left top; }\n'
			+'	div.box .player.playing .card { box-shadow:2px 2px 30px #fff; }\n'
			+'	.card.reversed { background-position:-'+(2*this.cardWidth)+'px -'+(4*this.cardHeight)+'px; }\n'
			+'	.card.hidden { display:none; }\n'
			+'	div.box .player a.name { display:block; color:#000; text-shadow:1px 1px 0px #666; font-weight:bold; font-size:14px; text-decoration:none; }\n'
			+'	div.box .player.playing a.name { color:#000; text-shadow:2px 2px 4px #fff; }\n'
			+'	.player0, .player2 { width:'+hHandWidth+'px; height:'+hHandHeight+'px; padding: '+this.cardMargin+'px 0; margin:0 auto; }\n'
			+'	.player2 { zoom:1.5; }\n'
			+'	.player0 .card, .player2 .card { float:left; }\n'
			+'	.player0 .card1, .player2 .card1 { margin-left:-'+hHandDecal+'px; }\n'
			+'	.player0 .card2, .player2 .card2 { margin-left:-'+hHandDecal+'px; }\n'
			+'	.player0 .card3, .player2 .card3 { margin-left:-'+hHandDecal+'px; }\n'
			+'	.player0 .card4, .player2 .card4 { margin-left:-'+hHandDecal+'px; }\n'
			+'	.player0 .card5, .player2 .card5 { margin-left:-'+hHandDecal+'px; }\n'
			+'	.player0 .card6, .player2 .card6 { margin-left:-'+hHandDecal+'px; }\n'
			+'	.player0 .card7, .player2 .card7 { margin-left:-'+hHandDecal+'px; }\n'
			+'	.player1, .player3 { width:'+vHandWidth+'px; height:'+vHandHeight+'px; padding:'+this.cardHeight+'px 0 0 0; }\n'
			+'	.player1 { float:right; }'
			+'	.player1 .card { float:right; clear:right; }'
			+'	.player3 { float:left; }'
			+'	.player3 .card { float:left; clear:left; }'
			+'	.player1 .card1, .player3 .card1 { margin-top:-'+vHandDecal+'px; }\n'
			+'	.player1 .card2, .player3 .card2 { margin-top:-'+vHandDecal+'px; }\n'
			+'	.player1 .card3, .player3 .card3 { margin-top:-'+vHandDecal+'px; }\n'
			+'	.player1 .card4, .player3 .card4 { margin-top:-'+vHandDecal+'px; }\n'
			+'	.player1 .card5, .player3 .card5 { margin-top:-'+vHandDecal+'px; }\n'
			+'	.player1 .card6, .player3 .card6 { margin-top:-'+vHandDecal+'px; }\n'
			+'	.player1 .card7, .player3 .card7 { margin-top:-'+vHandDecal+'px; }\n'
			+'	.trick.current { width:'+((this.cardWidth*2)+15)+'px; height:'+(this.cardHeight+Math.round(this.cardHeight/2)+15)+'px; padding:'+Math.round(this.cardHeight/4)+'px 0; margin:0 auto; }\n'
			//+'	.trick.previous { float:left; clear:left; zoom:0.5; width:'+((this.cardWidth*2)+15)+'px; height:'+(this.cardHeight+Math.round(this.cardHeight/2)+15)+'px; margin:'+Math.round(this.cardHeight/4)+'px 0 '+this.cardMargin+'px 0; }\n'
			+'	.trick .card { float:left; }\n'
			+'	.trick .card1 { margin-left:-15px; margin-top:15px; }\n'
			+'	.trick .card2 { margin-left:15px; margin-top:-'+Math.round(this.cardHeight/2)+'px; }\n'
			+'	.trick .card3 { margin-left:-15px; margin-top:-'+(Math.round(this.cardHeight/2)-15)+'px; }\n'
			+'</style>'
			+'<div class="popup">'
			+'</div>'
			+'<div class="notice">'
			+'</div>'
			+'<div class="player player1">'
			+'	<a href="#win'+w.id+'-player:1" class="name">Player 2</a>'
			+'	<a href="#win'+w.id+'-play:1:0" class="card card0 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:1:1" class="card card1 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:1:2" class="card card2 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:1:3" class="card card3 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:1:4" class="card card4 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:1:5" class="card card5 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:1:6" class="card card6 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:1:7" class="card card7 hidden"></a>'
			+'</div>'
			+'<div class="player player3">'
			+'	<a href="#win'+w.id+'-player:3" class="name">Player 4</a>'
			+'	<a href="#win'+w.id+'-play:3:0" class="card card0 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:3:1" class="card card1 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:3:2" class="card card2 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:3:3" class="card card3 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:3:4" class="card card4 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:3:5" class="card card5 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:3:6" class="card card6 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:3:7" class="card card7 hidden"></a>'
			+'</div>'
			+'<div class="player player0">'
			+'	<a href="#win'+w.id+'-player:0" class="name">Player 1</a>'
			+'	<a href="#win'+w.id+'-play:0:0" class="card card0 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:0:1" class="card card1 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:0:2" class="card card2 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:0:3" class="card card3 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:0:4" class="card card4 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:0:5" class="card card5 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:0:6" class="card card6 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:0:7" class="card card7 hidden"></a>'
			+'</div>'
			+'<div class="trick current">'
			+' <a href="#win'+w.id+'-trick:0" class="card card0 hidden"></a>'
			+' <a href="#win'+w.id+'-trick:1" class="card card1 hidden"></a>'
			+' <a href="#win'+w.id+'-trick:2" class="card card2 hidden"></a>'
			+' <a href="#win'+w.id+'-trick:3" class="card card3 hidden"></a>'
			+'</div>'/*
			+'<div class="trick previous">'
			+' <a href="#win'+w.id+'-trick:0" class="card card0 hidden"></a>'
			+' <a href="#win'+w.id+'-trick:1" class="card card1 hidden"></a>'
			+' <a href="#win'+w.id+'-trick:2" class="card card2 hidden"></a>'
			+' <a href="#win'+w.id+'-trick:3" class="card card3 hidden"></a>'
			+'</div>'*/
			+'<div class="player player2">'
			+'	<a href="#win'+w.id+'-player:2" class="name">Player 3</a>'
			+'	<a href="#win'+w.id+'-play:2:0" class="card card0 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:2:1" class="card card1 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:2:2" class="card card2 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:2:3" class="card card3 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:2:4" class="card card4 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:2:5" class="card card5 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:2:6" class="card card6 hidden"></a>'
			+'	<a href="#win'+w.id+'-play:2:7" class="card card7 hidden"></a>'
			+'</div>';
		this.gameView.appendChild(this.gameZone);
		if(w.app.screenType!='small')
			this.gameView.appendChild(this.gameLog);
		this.displayWindow.view.appendChild(this.gameView);
		this.popup('<h2>Bienvenue</h2>'
			+'<p>Pour commencer une nouvelle partie, entrez votre prenom ou surnom.</p>'
			+'<form action="#win'+w.id+'-newGame">'
			+'<p><label>Nom :'+(w.app.screenType=='small'?'<br />':' ')+'<input type="text" name="name" id="win'+w.id+'-name" value="'+(w.app.user?w.app.user.lastName:'')+'" /></label></p>'
			+'<p><input type="submit" value="Commencer une partie" /></p>'
			+'</form>');
		},
	prepare: function() {
		// Create players
		this.players=new Array(new ComputerPlayer(this,0),new ComputerPlayer(this,1),new HumanPlayer(this,2,$('win'+this.displayWindow.id+'-name').value),new ComputerPlayer(this,3));
		this.dealer=3;
		this.start();
		},
	start: function() {
		this.popup();
		this.taker=-1;
		this.curPlayer=-1;
		this.curTrick=null;
		this.tricks=new Array();
		this.trickNum=0;
		this.answer=null;
		// Deal first cards
		this.gameLog.innerHTML='';
		this.log('<h2># New game</h2>');
		this.log('<p><strong>'+this.players[this.dealer].name+'</strong> deals cards</p>');
		this.showNotice('<p><strong>'+this.players[this.dealer].name+'</strong> distribue.</p>');
		this.cardStack=new Array();
		for(var i=this.cards.length-1; i>=0; i--)
			{
			for(var j=this.suits.length-1; j>=0; j--)
				{
				this.cardStack.push({'card':i,'suit':j});
				}
			}
		var tpl;
		for(var i=0, k=this.players.length; i<k; i++)
			{
			this.players[i].reset();
			tpl='<p><strong>'+this.players[this.dealer].name+'</strong> give 3 cards to <strong>'+this.players[i].name+':</strong> ';
			for(var j=0; j<3; j++)
				{
				this.cardIndex=Math.floor(Math.random()*this.cardStack.length);
				this.players[i].takeCard(this.cardStack[this.cardIndex]);
				this.cardStack.splice(this.cardIndex,1);
				tpl+='<span style="font-size:15px; color:'+this.suits[this.players[i].handCards[this.players[i].handCards.length-1].suit].color+'">'+this.cards[this.players[i].handCards[this.players[i].handCards.length-1].card].symbol+this.suits[this.players[i].handCards[this.players[i].handCards.length-1].suit].symbol+'</span> ';
				}
			tpl+='</p>';
			this.log(tpl);
			this.render();
			}
		for(var i=0, k=this.players.length; i<k; i++)
			{
			tpl='<p><strong>'+this.players[this.dealer].name+'</strong> give 2 cards to <strong>'+this.players[i].name+':</strong> ';
			for(var j=0; j<2; j++)
				{
				this.cardIndex=Math.floor(Math.random()*this.cardStack.length);
				this.players[i].takeCard(this.cardStack[this.cardIndex]);
				this.cardStack.splice(this.cardIndex,1);
				tpl+='<span style="font-size:15px; color:'+this.suits[this.players[i].handCards[this.players[i].handCards.length-1].suit].color+'">'+this.cards[this.players[i].handCards[this.players[i].handCards.length-1].card].symbol+this.suits[this.players[i].handCards[this.players[i].handCards.length-1].suit].symbol+'</span> ';
				this.players[i].sortCards();
				}
			tpl+='</p>';
			this.log(tpl);
			this.render();
			}
		this.render(this.returnCard.bind(this),500);
		},
	returnCard: function() {
		// Return the card
		this.cardIndex=Math.floor(Math.random()*this.cardStack.length);
		tpl='<p><strong>'+this.players[this.dealer].name+'</strong> return the top card: ';
		tpl+='<span style="font-size:15px; color:'+this.suits[this.cardStack[this.cardIndex].suit].color+'">'+this.cards[this.cardStack[this.cardIndex].card].symbol+this.suits[this.cardStack[this.cardIndex].suit].symbol+'</span></p>';
		this.curTrick={'cards':new Array(this.cardStack[this.cardIndex])};
		this.log(tpl);
		this.showNotice('<p><strong>'+this.players[this.dealer].name+'</strong> retourne : '+this.cards[this.cardStack[this.cardIndex].card].symbol+' de '+this.displayWindow.locale[this.suits[this.cardStack[this.cardIndex].suit].name]+'.</p>');
		// Round 1
		this.log('<h2># Bidding : Round 1</h2>');
		this.bidder=this.dealer+1;
		this.render(this.bid.bind(this),1500);
		},
	bid: function() {
		this.players[this.bidder%4].bid(this.cardStack[this.cardIndex],this.bidCallback.bind(this));
		},
	bidCallback: function() {
		if(this.taker!=-1)
			{
			this.showNotice('<p><strong>'+this.players[this.taker].name+'</strong> prends en '+this.displayWindow.locale[this.suits[this.trumps].name]+'.</p>');
			this.render(this.bidDone.bind(this),1500);
			}
		else
			{
			this.showNotice('<p><strong>'+this.players[this.bidder%4].name+'</strong> passe son tour.</p>');
			if(this.bidder<this.dealer+4)
				{
				this.bidder++;
				this.bid();
				}
			else
				{
				// Round 2
				this.log('<h2># Bidding : Round 2</h2>');
				this.bidder=this.dealer+1;
				this.render(this.bid2.bind(this));
				}
			}
		},
	bid2: function() {
		this.players[this.bidder%4].bid2(this.cardStack[this.cardIndex],this.bid2Callback.bind(this));
		},
	bid2Callback: function() {
		if(this.taker!=-1)
			{
			this.showNotice('<p><strong>'+this.players[this.taker].name+'</strong> prend en '+this.displayWindow.locale[this.suits[this.trumps].name]+'.</p>');
			this.players[this.taker].sortCards();
			this.render(this.bidDone.bind(this),1500);
			}
		else
			{
			this.showNotice('<p><strong>'+this.players[this.bidder%4].name+'</strong> passe son tour.</p>');
			if(this.bidder<this.dealer+4)
				{
				this.bidder++;
				this.bid2();
				}
			else
				this.render(this.bidDone.bind(this));
			}
		},
	bidDone: function() {
		if(this.taker>=0)
			{
			this.log('<p><strong>'+this.players[this.dealer].name+'</strong> give the top card (<span style="font-size:15px; color:'+this.suits[this.cardStack[this.cardIndex].suit].color+'">'+this.cards[this.cardStack[this.cardIndex].card].symbol+this.suits[this.cardStack[this.cardIndex].suit].symbol+'</span>) to <strong>'+this.players[this.taker].name+'.</strong></p>');
			this.cardStack.splice(this.cardIndex,1);
			this.render();
			this.log('<p><strong>'+this.players[this.dealer].name+'</strong> deals cards</p>');
			this.showNotice('<p><strong>'+this.players[this.dealer].name+'</strong> distribue.</p>');
			for(var i=0, k=this.players.length; i<k; i++)
				{
				var tpl='<p><strong>'+this.players[this.dealer].name+'</strong> give 2 cards to <strong>'+this.players[i].name+':</strong> ';
				for(var j=(i==this.taker?1:0); j<3; j++)
					{
					this.cardIndex=(this.cardStack.length>1?Math.floor(Math.random()*this.cardStack.length):0);
					this.players[i].takeCard(this.cardStack[this.cardIndex]);
					this.cardStack.splice(this.cardIndex,1);
					if(!this.players[i].handCards[this.players[i].handCards.length-1])
						break;
					tpl+='<span style="font-size:15px; color:'+this.suits[this.players[i].handCards[this.players[i].handCards.length-1].suit].color+'">'+this.cards[this.players[i].handCards[this.players[i].handCards.length-1].card].symbol+this.suits[this.players[i].handCards[this.players[i].handCards.length-1].suit].symbol+'</span> ';
					this.players[i].sortCards();
					}
				tpl+='</p>';
				this.log(tpl);
				this.render();
				}
			this.log('<h2># Now playing !</h2>');
			this.leader=(this.dealer+1)%4;
			this.render(this.newTrick.bind(this));
			}
		else
			this.render(this.end.bind(this));
		},
	newTrick: function() {
			// Tricks
			this.log('<h2>Trick #'+(this.trickNum+1)+'</h2>');
			this.log('<p>'+this.players[this.leader].name+' is the leader</p>');
			this.curTrick={'num':this.trickNum,'cards':new Array()};
			this.tricks.push(this.curTrick);
			this.render(this.nextPlay.bind(this));
		},
	nextPlay: function() {
			this.curPlayer=(this.leader+this.curTrick.cards.length)%4;
			this.render();
			this.players[this.curPlayer].play(this.playCallback.bind(this));
		},
	playCallback: function() {
			this.showNotice('<p><strong>'+this.players[this.curTrick.cards[this.curTrick.cards.length-1].player].name+'</strong> joue : '+this.cards[this.curTrick.cards[this.curTrick.cards.length-1].card].symbol+' de '+this.displayWindow.locale[this.suits[this.curTrick.cards[this.curTrick.cards.length-1].suit].name]+'.</p>');
			this.log('<p></strong>'+this.players[this.curTrick.cards[this.curTrick.cards.length-1].player].name
				+'</strong> play <span style="font-size:15px; color:'+this.suits[this.curTrick.cards[this.curTrick.cards.length-1].suit].color+'">'
				+this.cards[this.curTrick.cards[this.curTrick.cards.length-1].card].symbol+this.suits[this.curTrick.cards[this.curTrick.cards.length-1].suit].symbol+'</span></p>');
			if(this.curTrick.cards.length<4)
				this.render(this.nextPlay.bind(this),500);
			else
				this.render(this.trickCallback.bind(this),1000);
		},
	trickCallback: function() {
			var hightestCard=this.curTrick.cards[0];
			for(var j=this.curTrick.cards.length-1; j>=0; j--)
				{
				if((this.curTrick.cards[j].suit==this.trumps&&(hightestCard.suit!=this.trumps||this.cards[this.curTrick.cards[j].card].trumpRank>this.cards[hightestCard.card].trumpRank))
					||(this.curTrick.cards[j].suit==this.curTrick.cards[0].suit&&hightestCard.suit!=this.trumps&&this.cards[this.curTrick.cards[j].card].suitRank>this.cards[hightestCard.card].suitRank))
					hightestCard=this.curTrick.cards[j];
				}
			tpl='</ul>';
			this.log(tpl);
			this.leader=hightestCard.player;
			var points=this.players[hightestCard.player].countPoints(this.curTrick);
			this.showNotice('<p><strong>'+this.players[this.leader].name+'</strong> remporte le pli ('+points+' points.).</p>');
			tpl='<p><strong>'+this.players[this.leader].name+'</strong>'
				+' win '+points+' points.</p>';
			this.log(tpl);
			this.curTrick=null;
			if(this.trickNum<7)
				{
				this.trickNum++;
				this.render(this.newTrick.bind(this),500);
				}
			else
				{
				this.render(this.result.bind(this));
				}
		},
	result: function() {
		this.log('<h2>Results</h2>');
		var scores=[(this.players[0].winnedPoints+this.players[2].winnedPoints),(this.players[1].winnedPoints+this.players[3].winnedPoints)];
		this.log('<p>Team 1 score : '+this.players[0].winnedPoints+'('+this.players[0].name+')'+'+'+this.players[2].winnedPoints+'('+this.players[2].name+')'+'='+scores[0]+'</p>');
		this.log('<p>Team 2 score : '+this.players[1].winnedPoints+'('+this.players[1].name+')'+'+'+this.players[3].winnedPoints+'('+this.players[3].name+')'+'='+scores[1]+'</p>');
		if(scores[0]==162)
			{
			this.log('<p>Team 1 did the great schelem !</p>');
			scores[0]=252;
			}
		else if(scores[1]==162)
			{
			this.log('<p>Team 2 did the great schelem !</p>');
			scores[1]=252;
			}
		else if(scores[this.taker%2]<81)
			{
			this.log('<p>Team '+((this.taker%2)+1)+' did not complete his bid !</p>');
			scores[this.taker%2]=0;
			scores[(this.taker+1)%2]=162;
			}
		// Calculer belote
		var tpl='<ul>';
		for(var i=0, k=this.players.length; i<k; i++)
			{
			tpl+='<li><strong>'+this.players[i].name+':</strong> have '+this.players[i].handCards.length+' cards in his hand.';
			}
		tpl+='</ul>';
		this.log(tpl);
		this.popup('<h2>Equipe'+(scores[0]>scores[1]?' 1 gagne':(scores[0]<scores[1]?' 2 gagne':'s a egalite'))+' !</h2>'
			+'<p>Equipe 1 : '+scores[0]+' points</p>'
			+'<p>Equipe 2 : '+scores[1]+' points</p>'
			+'<form action="#win'+this.displayWindow.id+'-newGame">'
			+'<p><input type="submit" value="Nouvelle partie" /></p>'
			+'</form>');
		this.log('<h2>The end !</h2>');
		this.dealer=(this.dealer+1)%4;
		},
	end: function(message) {
		this.popup('<h2>Aucune enchere</h2>'
			+'<p>Voulez-vous rejouer ?</p>'
			+'<form action="#win'+this.displayWindow.id+'-newGame">'
			+'<p><input type="submit" value="Nouvelle partie" /></p>'
			+'</form>');
		this.log('<h2>The end !</h2>');
		this.dealer=(this.dealer+1)%4;
	},
	log: function(message,debug) {
		if(this.displayWindow.options.debug||!debug)
			this.gameLog.innerHTML=message+this.gameLog.innerHTML;
		else
			console.log(message);
	},
	showNotice: function(html,delay) {
		if(this.hideNoticeDelay)
			clearTimeout(this.hideNoticeDelay);
		var popup=this.gameView.getElements('.notice')[0];
		popup.innerHTML+=html;
		popup.addClass('active');
		popup.setAnchoredPosition(popup.parentNode,{aHPos:'right',aVPos:10,hPos:'right',vPos:'top'});
		this.hideNoticeDelay=this.hideNotice.bind(this).delay((delay?delay:(this.displayWindow.options.speed?this.displayWindow.options.speed:1)*1500));
	},
	hideNotice: function() {
		var popup=this.gameView.getElements('.notice')[0];
		popup.innerHTML='';
		popup.removeClass('active');
	},
	updatePopup: function() {
		var popup=this.gameView.getElements('.popup')[0];
		if(popup.hasClass('active'))
			popup.setAnchoredPosition(popup.parentNode,{aHPos:'center',aVPos:10,hPos:'center',vPos:'top'});
	},
	popup: function(html) {
		var popup=this.gameView.getElements('.popup')[0];
		if(html)
			{
			popup.innerHTML=html;
			popup.addClass('active');
			popup.setAnchoredPosition(popup.parentNode,{aHPos:'center',aVPos:10,hPos:'center',vPos:'top'});
			}
		else
			{
			popup.removeClass('active');
			}
	},
	render: function(callback,delay) {
		for(var i=0; i<4; i++)
			{
			this.gameView.getElements('.trick.current .card'+i)[0].addClass('hidden');
			//this.gameView.getElements('.trick.previous .card'+i)[0].addClass('hidden');
			}
		for(var i=(this.curTrick?this.curTrick.cards.length-1:-1); i>=0; i--)
			{
			this.gameView.getElements('.trick.current .card'+i)[0].removeClass('hidden');
			this.gameView.getElements('.trick.current .card'+i)[0].removeClass('reversed');
			this.gameView.getElements('.trick.current .card'+i)[0].setStyle('background-position','-'+(this.cards[this.curTrick.cards[i].card].column*this.cardWidth)+'px -'+(this.suits[this.curTrick.cards[i].suit].row*this.cardHeight)+'px');
			}
		/*for(var i=(this.tricks&&this.tricks.length>1?this.tricks[this.tricks.length-2].cards.length-1:-1); i>=0; i--)
			{
			this.gameView.getElements('.trick.previous .card'+i)[0].removeClass('hidden');
			this.gameView.getElements('.trick.previous .card'+i)[0].removeClass('reversed');
			this.gameView.getElements('.trick.previous .card'+i)[0].setStyle('background-position','-'+(this.cards[this.tricks[this.tricks.length-2].cards[i].card].column*this.cardWidth)+'px -'+(this.suits[this.tricks[this.tricks.length-2].cards[i].suit].row*this.cardHeight)+'px');
			}*/
		for(var i=this.players.length-1; i>=0; i--)
			{
			if(this.curPlayer==i||(this.curPlayer==-1&&(this.bidder%4==i||this.leader==i)))
				{
				this.gameView.getElements('.player'+i)[0].addClass('playing');
				}
			else
				{
				this.gameView.getElements('.player'+i)[0].removeClass('playing');
				}
			this.gameView.getElements('.player'+i+' .name')[0].innerHTML=this.players[i].name+(i==this.taker?' a pris en '+this.displayWindow.locale[this.suits[this.trumps].name]:'');
			for(var j=this.players[i].handCards.length; j<8; j++)
				{
				this.gameView.getElements('.player'+i+' .card'+j)[0].addClass('hidden');
				}
			for(var j=this.players[i].handCards.length-1; j>=0; j--)
				{
				this.gameView.getElements('.player'+i+' .card'+j)[0].removeClass('hidden');
				if(this.players[i].isComputer)
					{
					this.gameView.getElements('.player'+i+' .card'+j)[0].addClass('reversed');
					}
				else
					{
					this.gameView.getElements('.player'+i+' .card'+j)[0].removeClass('reversed');
					this.gameView.getElements('.player'+i+' .card'+j)[0].setStyle('background-position','-'+(this.cards[this.players[i].handCards[j].card].column*this.cardWidth)+'px -'+(this.suits[this.players[i].handCards[j].suit].row*this.cardHeight)+'px');
					}
				}
			}
		if(delay&&callback&&this.displayWindow.options.speed)
			this.delayedCallback=callback.delay(delay*this.displayWindow.options.speed);
		else if(callback)
			callback();
	}
});

var Player=new Class({
	initialize: function(game, id, computer) {
		// Props
		this.game=game;
		this.id=id;
		this.isComputer=computer;
		this.riskLevel=Math.floor(Math.random()*3);
		this.handCards=new Array();
		this.reset();
	},
	reset: function(card) {
		this.leftCards=new Array();
		this.leftCards[0]=new Array(0,1,2,3,4,5,6,7);
		this.leftCards[1]=new Array(0,1,2,3,4,5,6,7);
		this.leftCards[2]=new Array(0,1,2,3,4,5,6,7);
		this.leftCards[3]=new Array(0,1,2,3,4,5,6,7);
		this.winnedPoints=0;
		this.handCards=new Array();
	},
	say: function(message,debug) {
		this.game.log('<p><strong>'+this.name+':</strong> '+message+'');
	},
	takeCard: function(card) {
		card.player=this.id;
		this.handCards.push(card);
	},
	sortCards: function() {
		var newHandCards=new Array();
		for(var i=this.game.suits.length-1; i>=0; i--)
			{
			for(var j=this.handCards.length-1; j>=0; j--)
				{
				if(this.handCards[j].suit==i)
					{
					newHandCards.push(this.handCards[j]);
					}
				}
			}
		this.handCards=newHandCards;
	},
	getHandScores: function(discardedSuit) {
		var handScores=new Array();
		var bestHandScore;
		for(var i=this.game.suits.length-1; i>=0; i--)
			{
			if(i!=discardedSuit)
				{
				var curHandScore={'suit':i,'score':this.getHandScore(i)};
				if(!bestHandScore)
					{
					bestHandScore=curHandScore;
					}
				else if(curHandScore.score>bestHandScore.score)
					{
					bestHandScore=curHandScore;
					}
				handScores.push(curHandScore);
				}
			}
		bestHandScore.best=true;
		this.say('My best handscore is in the suit '+this.game.suits[bestHandScore.suit].name+' is '+bestHandScore.score+'.');
		return handScores;
	},
	getHandScore: function(trumpSuit) {
		var handScore=0;
		var beloteCount=0;
		for(var i=this.handCards.length-1; i>=0; i--)
			{
			// Counting my trumps
			if(this.handCards[i].suit==trumpSuit)
				{
				if(this.handCards[i].card==4) // J
					handScore+=4;
				else if(this.handCards[i].card==2||this.handCards[i].card==7) // 9 A
					handScore+=3;
				else if(this.handCards[i].card==2||this.handCards[i].card==7) // Q K
					{
					beloteCount++;
					handScore+=1;
					}
				else // Other
					{
					handScore+=1;
					}
				}
			// Other strong cards
			else if(this.handCards[i].card==7) // A
				{
				handScore+=2;
				}
			else if(this.handCards[i].card==3) // 10 + other
				{
				for(var j=this.handCards.length-1; j>=0; j--)
					{
					if(this.handCards[i].suit==this.handCards[j].suit)
						{
						handScore+=1;
						break;
						}
					}
				}
			}
		// Belote
		if(beloteCount==2)
			handScore+=1;
		this.say('My handscore for the suit '+this.game.suits[trumpSuit].name+' is '+handScore+'.');
		return handScore;
	},
	bidCallback: function(suit) {
		if(this.callback)
			{
			this.game.popup();
			if(suit!=-1)
				{
				this.handCards[this.handCards.length-1].player=this.id;
				this.game.taker=this.id;
				this.game.trumps=suit;
				this.game.curTrick=null;
				this.say('I take in <span style="font-size:15px; color:'+this.game.suits[suit].color+'">'+this.game.suits[suit].symbol+'</span>');
				}
			else
				{
				this.say('I Pass');
				this.handCards.pop();
				}
			var callback=this.callback;
			this.callback=null;
			callback();
			}
	},
	getPlayOptions: function() {
		var playOptions=new Array();
		var trick=this.game.curTrick;
		// View if there are trumps in the trick and what is the hightest
		var trickHasTrumps=false;
		var hightestTrickTrump=-1;
		for(var i=trick.cards.length-1; i>=0; i--)
			{
			if(trick.cards[i].suit==this.game.trumps)
				{
				if(!trickHasTrumps)
					{
					hightestTrickTrump=i;
					trickHasTrumps=true;
					}
				else if(this.game.cards[trick.cards[i].card].trumpRank>this.game.cards[trick.cards[hightestTrickTrump].card].trumpRank)
					{
					hightestTrickTrump=i;
					}
				}
			}
		// Get my lowest value card left
		var lowestValueCard=0;
		for(var i=this.handCards.length-1; i>0; i--)
			{
			if(this.game.cards[this.handCards[i].card].suitVal<this.game.cards[this.handCards[lowestValueCard].card].suitVal)
				{
				lowestValueCard=i;
				}
			}
		// Ranking my left trumps
		var myHightestTrumps=new Array();
		var myLowestTrumps=new Array();
		var myLeftTrumps=new Array();
		for(var i=this.handCards.length-1; i>=0; i--)
			{
			if(this.handCards[i].suit==this.game.trumps)
				myLeftTrumps.push(i);
			if(this.handCards[i].suit==this.game.trumps)
				{
				if(trickHasTrumps==false||this.game.cards[this.handCards[i].card].trumpVal>this.game.cards[trick.cards[hightestTrickTrump].card].trumpVal)
					myHightestTrumps.push(i);
				else
					myLowestTrumps.push(i);
				}
			}
		this.say('I have '+myLeftTrumps.length+' trumps on '+this.leftCards[this.game.trumps].length+' left trumps');
		if(!trick.cards.length)
			{
			this.say('No cards in the trick, i\'m the leader');
			this.say('Reviewing my cards',true);
			for(var i=this.handCards.length-1; i>=0; i--)
				{
				this.say('Card #'+i+' - '+this.game.cards[this.handCards[i].card].name+' of '+this.game.suits[this.handCards[i].suit].name+'');
				if(this.handCards[i].suit==this.game.trumps)
					{
					this.say('The card is a trump');
					if(this.leftCards[this.handCards[i].suit].length<=myLeftTrumps.length)
						{
						this.say('I\'m the last to have trumps, will not play this card.');
						continue;
						}
					if(this.id%2==this.game.taker%2&&this.handCards[i].card==4)
						{
						this.say('The card is the hightest trump, i\'m in the taker team, i play the card');
						playOptions.push({'card':i,'force':9});
						continue;
						}
					// Look if the card is the hightest trump left in the game
					var hightest=true;
					for(var j=this.leftCards[this.handCards[i].suit].length-1; j>=0; j--)
						{
						if(this.game.cards[this.leftCards[this.handCards[i].suit][j]].trumpRank>this.game.cards[this.handCards[i].card].trumpRank)
							hightest=false;
						}
					if(hightest)
						{
						if(this.id%2==this.game.taker%2&&this.id!=this.game.taker&&this.leftCards[this.handCards[i].suit].length>=6)
							{
							this.say('The card is the hightest trump left, i\'m in the taker team and there is a lot of trumps left in the game, i play the card');
							playOptions.push({'card':i,'force':9});
							}
						else if(this.leftCards[this.handCards[i].suit].length/3<myLeftTrumps.length)
							{
							this.say('The card is the hightest trump left and i probably have enought trumps to be the last to have trumps');
							playOptions.push({'card':i,'force':9});
							}
						else
							{
							this.say('The card is the hightest trump (force 1) ['+this.leftCards[this.handCards[i].suit].length+'/3<'+myLeftTrumps.length+']');
							playOptions.push({'card':i,'force':1});
							}
						}
					if(this.id%2==this.game.taker%2&&this.id!=this.game.taker&&this.game.cards[this.handCards[i].card].trumpRank==0&&this.leftCards[this.handCards[i].suit].length>=6)
						{
						this.say('The card is a null trump, i\'m in the taker team, there are lot of trumps left (force 1)');
						playOptions.push({'card':i,'force':1});
						}
					else	if(this.game.cards[this.handCards[i].card].trumpRank==0)
						{
						this.say(' I\'ve a null trump, i can try to know what my partner want me to play (force 0.5)');
						playOptions.push({'card':i,'force':0.5});
						}
					else if(this.game.cards[this.handCards[i].card].trumpRank<=3)
						{
						this.say(' I\'ve a low trump, i can try to know what my partner want me to play (force 0)');
						playOptions.push({'card':i,'force':0});
						}
					}
				else
					{ // voir aussi faire couper adversaire en mémorisant qui coupe
					// Si j'ai le prochain atout maitre et que j'ai assez d'atout pour éliminer les autres, je joue l'atout.
					this.say('The card is not a trump (left cards of this suit:'+this.leftCards[this.handCards[i].suit].length+')');
					// Look if the card is the hightest left in his suit
					var hightest=true;
					for(var j=this.leftCards[this.handCards[i].suit].length-1; j>=0; j--)
						{
						if(this.game.cards[this.leftCards[this.handCards[i].suit][j]].suitRank>this.game.cards[this.handCards[i].card].suitRank)
							hightest=false;
						}
					if(hightest)
						{
						if(!this.leftCards[this.game.trumps].length)
							{
							this.say('The card is the hightest of his suit and there is no more trumps, i play the card');
							playOptions.push({'card':i,'force':9});
							}
						else	if(this.leftCards[this.handCards[i].suit].length==7)
							{
							this.say('The card is the hightest of his suit and the suit has not been played yet, i try to use her');
							playOptions.push({'card':i,'force':9});
							}
						else
							{
							this.say('The card is the hightest of his suit (force:0.5)');
							var force=0.5;
							if(this.leftCards[this.game.trumps].length<2)
								{
								this.say('There are less than 2 trumps (force ++)');
								force++
								}
							if(trick.num<4)
								{
								this.say('There are some more tricks to play (force ++)');
								force+=0.5;
								}
							if(this.leftCards[this.handCards[i].suit].length>5)
								{
								this.say('There are a lot of cards of the same suit left (force ++)'); // Should count the number of cards of this suit in my hand
								force++
								}
							playOptions.push({'card':i,'force':force});
							}
						}
					else if(this.leftCards[this.handCards[i].suit].length>=6&&this.game.cards[this.handCards[i].card].suitVal<=3)
						{
						this.say('The suit of this card has not been played so much yet and the card is low (force 0)');
						var force=0;
						if(this.leftCards[this.handCards[i].suit].length==8)
							{
							this.say('The suit of this card has not been played at all (force +=0.5)');
							force+=0.5;
							}
						if(this.game.cards[this.handCards[i].card].suitVal==0)
							{
							this.say('The card is null (force +=0.5)');
							force+=0.5;
							}
						// Look if i have the next hightest card of this suit
						var nextHightest=false;
						for(var j=this.handCards.length-1; j>=0; j--) // Warning, the player don't take in count current played cards in the trick
							{
							if(this.leftCards[this.handCards[j].suit].length<2||this.handCards[j].card==this.leftCards[this.handCards[j].suit][this.leftCards[this.handCards[j].suit].length-2].card)
								nextHightest=true;
							}
						if(nextHightest)
							{
							this.say('I have the next hightest card (force +=0.5)');
							force+=0.5;
							}
						playOptions.push({'card':i,'force':force});
						}
					}
				}
			}
		else
			{
			// Searching asked cards
			var askedSuitCards=new Array();
			for(var i=this.handCards.length-1; i>=0; i--)
				{
				if(this.handCards[i].suit==trick.cards[0].suit)
					{
					askedSuitCards.push(i);
					}
				}
			// Thinking
			if(askedSuitCards.length==1)
				{
				this.say('I have only one card of the asked suit, i have to play her.');
				playOptions.push({'card':askedSuitCards[0],'force':9});
				}
			else
				{
				if(trick.cards[0].suit==this.game.trumps)
					{
					if(myLeftTrumps.length==1)
						{
						this.say('The leader played a trump and i\'ve only one trump, i\'ve to play him');
						playOptions.push({'card':myLeftTrumps[0],'force':9});
						}
					else if(myLeftTrumps.length)
						{
						this.say('The leader played trumps and i\'ve some trumps...');
						if(myHightestTrumps.length==1)
							{
							this.say('I have 1 highter trump, i play him.</p>');
							playOptions.push({'card':myHightestTrumps[0],'force':9});
							}
						else if(myHightestTrumps.length)
							{
							if(trick.length==3)
								{
								this.say('I have some highter trumps, i\'m the last to play, i play the lowest of the hightest.');
								playOptions.push({'card':myHightestTrumps[0],'force':9});
								}
							else
								{
								this.say('I have some highter trumps, i play the hightest if he is the hightest left in the game.'); //?
								playOptions.push({'card':myHightestTrumps[0],'force':9});
								}
							}
						else
							{
							this.say('I just have lower trumps and my partner is not leader, i play the lowest.');
							var lowestTrump=myLowestTrumps[0];
							for(var i=myLowestTrumps.length-1; i>0; i--)
								{
								if(this.game.cards[this.handCards[myLowestTrumps[i]].card].trumpVal<this.game.cards[this.handCards[lowestTrump].card].trumpVal)
									lowestTrump=myLowestTrumps[i];
								}
							playOptions.push({'card':lowestTrump,'force':9});
							//this.say('I just have 1 lower trumps and my partner is the leader, i play the lowest with points.');
							}
						}
					else
						{
						if(trick.cards[hightestTrickTrump].player%2==this.id%2)
							{
							this.say('The leader played trumps and i\'ve no trumps, my partner wins, i\'m playing a non best hight value card');
							playOptions.push({'card':this.handCards.length-1,'force':9});
							}
						else
							{
							this.say('The leader played trumps and i\'ve no trumps, my partner does not win, i\'m playing a low value card');
							playOptions.push({'card':lowestValueCard,'force':9});
							}
						}
					}
				else if(askedSuitCards.length) // The leader asked a suit i can serve
					{
					if(!trickHasTrumps) // Pas coupé
						{
						// Search the lowest and the hightest cards i own for this suit
						var lowest=0;
						var hightest=0;
						for(var j=askedSuitCards.length-1; j>0; j--)
							{
							if(this.game.cards[this.handCards[askedSuitCards[j]].card].suitRank<this.game.cards[this.handCards[askedSuitCards[lowest]].card].suitRank)
								lowest=j;
							if(this.game.cards[this.handCards[askedSuitCards[j]].card].suitRank>this.game.cards[this.handCards[askedSuitCards[hightest]].card].suitRank)
								hightest=j;
							}
						// Look if the card is the hightest of in the game
						var hightestLeft=true;
						for(var k=this.leftCards[trick.cards[0].suit].length-1; k>=0; k--)
							{
							if(this.game.cards[this.leftCards[trick.cards[0].suit][k]].suitRank>this.game.cards[this.handCards[askedSuitCards[hightest]].card].suitRank)
								hightestLeft=false;
							}
						for(var k=trick.cards.length-1; k>=0; k--)
							{
							if(trick.cards[k].suit==trick.cards[0].suit&&this.game.cards[trick.cards[k].card].suitRank>this.game.cards[this.handCards[askedSuitCards[hightest]].card].suitRank)
								hightestLeft=false;
							}
						if(hightestLeft&&trick.cards.length==3)
							{
							this.say('The card is the hightest of his suit and i\'m the last to play, i play the card');
							playOptions.push({'card':askedSuitCards[hightest],'force':9});
							}
						else	if(hightestLeft)
							{
							this.say('The card is the hightest of his suit');
							playOptions.push({'card':askedSuitCards[hightest],'force':9});
							}
						else // Could also give points to the partner if he is the winner
							{
							this.say('I\'ve some cards of the asked suit, i play the lowest');
							playOptions.push({'card':askedSuitCards[lowest],'force':9});
							}
						}
					else // Suit has been trumped
						{
						if(trick.cards[hightestTrickTrump].player%2==this.id%2&&trick.length==3)
							{
							this.say(' A player throwed a trump, my partner wins, i\'m the last to play, putting the non-master card with the best value (not implemented)');
							playOptions.push({'card':lowestValueCard,'force':9});
							}
						else
							{
							this.say('A player throwed a trump, my partner does not win, i\'m playing a low value card.');
							var lowestCard=0;
							for(var j=askedSuitCards.length-1; j>0; j--)
								{
								if(this.game.cards[this.handCards[askedSuitCards[j]].card].suitRank<this.game.cards[this.handCards[askedSuitCards[lowestCard]].card].suitRank)
									lowestCard=j;
								}
							playOptions.push({'card':askedSuitCards[lowestCard],'force':9});
							}
						}
					}/*
				else	if(myLeftTrumps.length==1)
					{
					// NONON, vérifier si partenaire est maitre, laisser traiter par la suite
					this.say('I can\'t play cards of the asked suit, and i\'ve only one trump, i play him');
					playOptions.push({'card':myLeftTrumps[0],'force':9});
					}*/
				else if(myLeftTrumps.length)
					{
					if(!trickHasTrumps) // No trumps in the trick
						{
						var master=0;
						for(var i=trick.cards.length-1; i>0; i--)
							{
							if(this.game.cards[trick.cards[i].card].suitVal>this.game.cards[trick.cards[master].card].suitVal)
								master=i;
							}
						if(trick.cards[master].player%2==this.id%2)
							{
							this.say('My partner wins, i don\'t have to trump, i play the lowest card.');
							playOptions.push({'card':lowestValueCard,'force':9});
							}
						else if(trick.cards.length==3)
							{
							this.say('I can\'t play cards of the asked suit, i\'ve some trumps and i\'m the last to play, i play my lowest trump.');
							playOptions.push({'card':(myLowestTrumps.length?myLowestTrumps[0]:myLeftTrumps[0]),'force':9});
							}
						else
							{
							this.say('I can\'t play cards of the asked suit, and i\'ve some trumps, i play one of the hightest');
							playOptions.push({'card':(myHightestTrumps.length?myHightestTrumps[0]:myLeftTrumps[0]),'force':9});
							}
						}
					else // Suit has been trumped
						{
						if(trick.cards[hightestTrickTrump].player%2==this.id%2&&trick.length==3)
							{
							this.say(' A player throwed a trump, my partner wins, i\'m the last to play, i\'ve trumps, but i dont have to serve them, i put the non-master card with the best value (not implemented)');
							playOptions.push({'card':lowestValueCard,'force':9});
							}
						else
							{
							if(myLeftTrumps.length==1)
								{
								this.say('A player throwed a trump, my partner does not win, i have only one trump, i play him');
								playOptions.push({'card':myLeftTrumps[0],'force':9});
								}
							else if(myLeftTrumps.length)
								{
								if(myHightestTrumps.length)
									{
									this.say('A player throwed a trump, my partner does not win, i have a highter trump, i play him');
									playOptions.push({'card':myHightestTrumps[0],'force':9});
									}
								else
									{
									this.say('A player throwed a trump, my partner does not win, i have a lowest trump, i have to play him');
									playOptions.push({'card':myLeftTrumps[0],'force':9});
									}
								}
							else
								{
								this.say('A player throwed a trump, my partner does not win, i have no trump, i\'m playing a low value trump card');
								playOptions.push({'card':lowestValueCard,'force':9});
								}
							}
						}
					}
				else
					{
					this.say('I can\'t play cards of the asked suit, i have no trump, i\'m playing a low value card');
					playOptions.push({'card':lowestValueCard,'force':9});
					}
				}
			}
		if(playOptions.length==0)
			{
			this.game.log('<p><strong>'+this.name+':</strong>No play options at round '+(trick.num+1)+' for player '+this.name+'');
			playOptions.push({'card':this.handCards.length-1,'force':9});
			}
		return playOptions;
	},
	playCallback: function(cardIndex) {
		if(this.callback&&this.game.taker>=0&&this.id==(this.game.leader+this.game.curTrick.cards.length)%4)
			{
			this.throwCard(this.handCards[cardIndex]);
			var callback=this.callback;
			this.callback=null;
			callback();
			}
	},
	introduceMyself: function() {
			this.game.showNotice('<p><strong>'+this.name+'</strong> : Mon nom est  '+this.name+', je suis un '+(this.isComputer?'ordinateur, j\'ai un niveau de risque egal a '+this.riskLevel:'humain.</p>'));
	},
	throwCard: function(card) {
		this.game.curTrick.cards.push(card);
		this.handCards.splice(this.handCards.indexOf(card),1);
		for(var i=this.game.players.length-1; i>=0; i--)
			this.game.players[i].learnPlayedCard(card);
	},
	learnPlayedCard: function(card) {
		// Discount card suit
		this.leftCards[card.suit].splice(this.leftCards[card.suit].indexOf(card.card),1);
		},
	countPoints: function(trick) {
		var points=0;
		for(var i=trick.cards.length-1; i>=0; i--)
			{
			points+=(trick.cards[i].suit==this.game.trumps?this.game.cards[trick.cards[i].card].trumpVal:this.game.cards[trick.cards[i].card].suitVal);
			}
		if(trick.num==7)
			points+=10;
		this.winnedPoints+=points;
		return points;
		}
});

var HumanPlayer=new Class({
	Extends: Player,
	initialize: function(game, id,name) {
		this.name=(name?name:'Player '+(id+1));
		this.riskLevel=0;
		// Initializing window
		this.parent(game,id,false);
	},
	bid: function(topCard, callback) {
		this.handCards.push(topCard);
			var score=this.getHandScore(topCard.suit);
			this.game.popup('<h2>Encheres : 1er Tour</h2>'
				+'<p>Souhaitez-vous prendre en '+this.game.displayWindow.locale[this.game.suits[topCard.suit].name]+'?</p>'
				+'<form action="#">'
				+'<p><input type="submit" value="Oui" formaction="#win'+this.game.displayWindow.id+'-bid:'+this.id+':'+topCard.suit+'" />'
				+' <input type="submit" value="Non" formaction="#win'+this.game.displayWindow.id+'-bid:'+this.id+':-1" /></p>'
				+'</form>');
		this.callback=callback;
	},
	bid2: function(topCard, callback) {
		this.handCards.push(topCard);
			var scores=this.getHandScores(topCard.suit);
			var tpl='<h2>Encheres : 2eme Tour</h2>'
				+'<p>Souhaitez-vous prendre ?</p>'
				+'<form action="#"><p>';
			for(var i=scores.length-1; i>=0; i--)
				{
				tpl+='<input type="submit" value="'+this.game.displayWindow.locale[this.game.suits[scores[i].suit].name]+'"'
					+' formaction="#win'+this.game.displayWindow.id
					+'-bid:'+this.id+':'+scores[i].suit+'" /> ';
				}
			tpl+='<input type="submit" value="Non" formaction="#win'+this.game.displayWindow.id+'-bid:'+this.id+':-1" />'
				+'</p></form>';
		this.game.popup(tpl);
		this.callback=callback;
	},
	play: function(callback) {
		var playOptions=this.getPlayOptions();
		this.say('Let\'s choose the best play option');
		var bestPlayOption=playOptions[0];
		for(var i=playOptions.length-1; i>0; i--)
			{
			if(playOptions[i].force>bestPlayOption.force)
				bestPlayOption=playOptions[i];
			}
		this.say('Best option is card #'+bestPlayOption.card+' - '+this.game.cards[this.handCards[bestPlayOption.card].card].name+' of '+this.game.suits[this.handCards[bestPlayOption.card].suit].name+' with force of '+bestPlayOption.force+'.');
		this.callback=callback;
	}
});

var ComputerPlayer=new Class({
	Extends: Player,
	initialize: function(game, id) {
		switch(id)
			{
			case 0:
				this.name='Bill';
				break;
			case 1:
				this.name='Bob';
				break;
			case 2:
				this.name='John';
				break;
			default:
				this.name='Jack';
			}
		this.riskLevel=Math.floor(Math.random()*3);
		// Initializing window
		this.parent(game,id,true);
	},
	play: function(callback) {
		this.callback=callback;
		var playOptions=this.getPlayOptions();
		this.say('Let\'s choose the best play option');
		var bestPlayOption=playOptions[0];
		for(var i=playOptions.length-1; i>0; i--)
			{
			if(playOptions[i].force>bestPlayOption.force)
				bestPlayOption=playOptions[i];
			}
		this.say('Best option is card #'+bestPlayOption.card+' - '+this.game.cards[this.handCards[bestPlayOption.card].card].name+' of '+this.game.suits[this.handCards[bestPlayOption.card].suit].name+' with force of '+bestPlayOption.force+'.');
		this.playCallback(bestPlayOption.card);
	},
	bid: function(topCard, callback) {
		this.callback=callback;
		this.handCards.push(topCard);
		var score=this.getHandScore(topCard.suit);
		if(score>8-this.riskLevel)
			{
			this.bidCallback(topCard.suit);
			}
		else
			{
			this.bidCallback(-1);
			}
	},
	bid2: function(topCard, callback) {
		this.callback=callback;
		this.handCards.push(topCard);
		var scores=this.getHandScores(topCard.suit);
		for(var i=scores.length-1; i>=0; i--)
			{
			if(scores[i].best)
				{
				if(scores[i].score>8-this.riskLevel)
					{
					this.bidCallback(scores[i].suit);
					}
				else
					{
					this.bidCallback(-1);
					}
				}
			}
	}
});