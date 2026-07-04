$(document).ready(function(){
	//初始化
	var date1 = new Date();
	var date2 = new Date();
	date1.setDate(date1.getDate()-3);
	date2.setDate(date2.getDate()+3);
	initialization_ds(date1,date2);
	//点击日期事件
	$(document).on('click','.dateTimeSelector-wraper .date',function(){
		if (!$(this).hasClass('disabled'))
		{
			var parent_ = $(this).parents('.dates');
		    parent_.find('.actived').each(function(){
				$(this).removeClass('actived');
			});
			$(this).addClass('actived');
			//目标容器
			var target_ =  $('#adddate');
			var bid = $(this).parents('.dateTimeSelector').attr('bid');
			var currdatebox = $(this).parents('.dateTimeSelector').find('.dateTimeSelector-header').find('.month-slider .curr-date');
			
			console.log(currdatebox.attr('month'));
			var timebox_ = $(this).parents('.dateTimeSelector').find('.time');
			console.log(timebox_.find('.clock').val());
			var year = isNaN(currdatebox.attr('year'))?2000:parseInt(currdatebox.attr('year'));
			var month = isNaN(currdatebox.attr('month'))?1:parseInt(currdatebox.attr('month'));
			var day = isNaN($(this).html())?1:parseInt($(this).html());
			console.log(currdatebox.attr('month'));
			var hour = timebox_.find('.clock').val();
			var minute = timebox_.find('.minute').val();
			var second = timebox_.find('.second').val();
			day = day>31?1:day;
			day = day<=0?1:day;
			switch(bid)
			{
				case '0':
					target_.find('.start-datetime .date').attr('year',year);
					target_.find('.start-datetime .date').attr('month',month);
					target_.find('.start-datetime .date').attr('day',day);
					target_.find('.start-datetime .date').html((month>9?month:'0'+month) + '-' +(day>9?day:'0'+day));
					target_.find('.start-datetime .time').attr('hour',hour);
					target_.find('.start-datetime .time').attr('minute',minute);
					target_.find('.start-datetime .time').attr('second',second);
					target_.find('.start-datetime .time').html(hour+':'+minute+':'+second);
					break;
				case '1':
					target_.find('.end-datetime .date').attr('year',year);
					target_.find('.end-datetime .date').attr('month',month);
					target_.find('.end-datetime .date').attr('day',day);
					target_.find('.end-datetime .date').html((month>9?month:'0'+month) + '-' +(day>9?day:'0'+day));
					target_.find('.end-datetime .time').attr('hour',hour);
					target_.find('.end-datetime .time').attr('minute',minute);
					target_.find('.end-datetime .time').attr('second',second);
					target_.find('.end-datetime .time').html(hour+':'+minute+':'+second);
					break;
			}
			
		}
	});
	//绑定弹出事件
	$(document).on('click','.datetime-select',function(){
		$('.dateTimeSelector-wraper').show();
		var x,y,h,b,t;
		x = $(this).offset().left;
		y = $(this).offset().top;
		h = $(this).height();
		t = $(this);
		b = {};
		b.t = function(){
			var w=0;
			try
			{
				w = parseInt(t.css("borderTopWidth"));
			}catch(e){}			
			return w;
		}();
		b.b = function(){
			var w=0;
			try
			{
				w = parseInt(t.css("borderBottomWidth"));
			}catch(e){}			
			return w;
		}();
		console.log('x:'+x+' y:'+y+' h:'+h+' b.t:'+b.t+' b.b:'+b.b);
		$('.dateTimeSelector-wraper').css({'left':x+'px','top':y+h+b.t+b.b+'px'});
	});
	//confirm-datetime
	$(document).on('click','.dateTimeSelector-wraper .confirm-datetime',function(){
		setDateTime('#adddate');
		$('.dateTimeSelector-wraper').hide();
	});
	//时间选择器
	$(document).on('change','.dateTimeSelector .time select',function(){
		setDateTime('#adddate');
	});
	//前进月份
	$(document).on('click','.dateTimeSelector-wraper .slide-to-right',function(){
		//var dates_el_  = $(this).parents('.dateTimeSelector').find('.dates');
		var curr_month = $(this).parents('.month-slider').find('.curr-date').attr('month');
		var curr_year  = $(this).parents('.month-slider').find('.curr-date').attr('year');
		var bid  = $(this).parents('.dateTimeSelector').attr('bid');
		curr_month = !isNaN(parseInt(curr_month))?parseInt(curr_month):01;
		curr_year = !isNaN(parseInt(curr_year))?parseInt(curr_year):2000;
		var newDateTime = '2000-01-01';		
		if (curr_month===12)
		{
			newDateTime = (curr_year+1) + '-' + '01' + '-01';	
			console.log(newDateTime);
		}
		if (curr_month!==12)
		{
			newDateTime = curr_year + '-' + ((curr_month+1).toString().length>1?(curr_month+1):'0'+(curr_month+1)) + '-01';			
		}
		
		//console.log('m'+curr_month+'y'+curr_year);
		
		if (bid==='0')
		{
			fillLeftBox(new Date(newDateTime));
		}
		if (bid==='1')
		{
			fillRightBox(new Date(newDateTime));
		}
	});
	//后退月份
	$(document).on('click','.dateTimeSelector-wraper .slide-to-left',function(){
		//var dates_el_  = $(this).parents('.dateTimeSelector').find('.dates');
		var curr_month = $(this).parents('.month-slider').find('.curr-date').attr('month');
		var curr_year  = $(this).parents('.month-slider').find('.curr-date').attr('year');
		var bid  = $(this).parents('.dateTimeSelector').attr('bid');
		curr_month = !isNaN(parseInt(curr_month))?parseInt(curr_month):01;
		curr_year = !isNaN(parseInt(curr_year))?parseInt(curr_year):2000;
		var newDateTime = '2000-01-01';		
		if (curr_month===1)
		{
			newDateTime = (curr_year-1) + '-' + '12' + '-01';	
			console.log(newDateTime);
		}
		if (curr_month!==1)
		{
			newDateTime = curr_year + '-' + ((curr_month-1).toString().length>1?(curr_month-1):'0'+(curr_month-1)) + '-01';			
		}
		
		//console.log('m'+curr_month+'y'+curr_year);
		
		if (bid==='0')
		{
			fillLeftBox(new Date(newDateTime));
		}
		if (bid==='1')
		{
			fillRightBox(new Date(newDateTime));
		}
	});
	var isOn = false;
	//鼠标移入事件
	$(document).on('mouseover','.dateTimeSelector-wraper',function(){
		isOn = true ;
	});
	//鼠标移出事件
	$(document).on('mouseleave','.dateTimeSelector-wraper',function(){
		isOn = false ;
	});
	$('body').click(function(){
		if (!isOn)
		{
			$('.dateTimeSelector-wraper').hide();
		}		
	});
});
//时间赋值
function setDateTime(targetElement_){	
	//目标容器
	var target_ = $(targetElement_);
	var year1   = $('.dateTimeSelector-wraper .left .curr-date').attr('year');
	var month1  = $('.dateTimeSelector-wraper .left .curr-date').attr('month');
	var day1    = $('.dateTimeSelector-wraper .left .dates .actived').html();
	var hour1   = $('.dateTimeSelector-wraper .left .time .clock').val();
	var minute1 = $('.dateTimeSelector-wraper .left .time .minute').val();
	var second1 = $('.dateTimeSelector-wraper .left .time .second').val();
	var year2   = $('.dateTimeSelector-wraper .right .curr-date').attr('year');
	var month2  = $('.dateTimeSelector-wraper .right .curr-date').attr('month');
	var day2    = $('.dateTimeSelector-wraper .right .dates .actived').html();
	var hour2   = $('.dateTimeSelector-wraper .right .time .clock').val();
	var minute2 = $('.dateTimeSelector-wraper .right .time .minute').val();
	var second2 = $('.dateTimeSelector-wraper .right .time .second').val();

	target_.find('.start-datetime .date').attr('year',year1);
	target_.find('.start-datetime .date').attr('month',month1);
	target_.find('.start-datetime .date').attr('day',day1);
	target_.find('.start-datetime .date').html((month1>9?month1:'0'+month1) + '-' +(day1>9?day1:'0'+day1));
	target_.find('.start-datetime .time').attr('hour',hour1);
	target_.find('.start-datetime .time').attr('minute',minute1);
	target_.find('.start-datetime .time').attr('second',second1);
	target_.find('.start-datetime .time').html(hour1+':'+minute1+':'+second1);

	target_.find('.end-datetime .date').attr('year',year2);
	target_.find('.end-datetime .date').attr('month',month2);
	target_.find('.end-datetime .date').attr('day',day2);
	target_.find('.end-datetime .date').html((month2>9?month2:'0'+month2) + '-' +(day2>9?day2:'0'+day2));
	target_.find('.end-datetime .time').attr('hour',hour2);
	target_.find('.end-datetime .time').attr('minute',minute2);
	target_.find('.end-datetime .time').attr('second',second2);
	target_.find('.end-datetime .time').html(hour2+':'+minute2+':'+second2);
}
function initialization_ds(date1,date2)
{
	'use strict';
	fillTimeSelector();
	fillLeftBox(date1);
	fillRightBox(date2);
	//目标容器
		var target_ = $('#adddate');
		var year1   = date1.getFullYear();
		var month1  = parseInt(date1.getMonth().toString())+1;
		var day1    = date1.getDate();
		var hour1   = '00';
		var minute1 = '00';
		var second1 = '00';
		var year2   = date2.getFullYear();
		var month2  = parseInt(date2.getMonth().toString())+1;
		var day2    = date2.getDate();
		var hour2   = '23';
		var minute2 = '59';
		var second2 = '59';
		
		target_.find('.start-datetime .date').attr('year',year1);
		target_.find('.start-datetime .date').attr('month',month1);
		target_.find('.start-datetime .date').attr('day',day1);
		target_.find('.start-datetime .date').html((month1>9?month1:'0'+month1) + '-' +(day1>9?day1:'0'+day1));
		target_.find('.start-datetime .time').attr('hour',hour1);
		target_.find('.start-datetime .time').attr('minute',minute1);
		target_.find('.start-datetime .time').attr('second',second1);
		target_.find('.start-datetime .time').html(hour1+':'+minute1+':'+second1);
		
		target_.find('.end-datetime .date').attr('year',year2);
		target_.find('.end-datetime .date').attr('month',month2);
		target_.find('.end-datetime .date').attr('day',day2);
		target_.find('.end-datetime .date').html((month2>9?month2:'0'+month2) + '-' +(day2>9?day2:'0'+day2));
		target_.find('.end-datetime .time').attr('hour',hour2);
		target_.find('.end-datetime .time').attr('minute',minute2);
		target_.find('.end-datetime .time').attr('second',second2);
		target_.find('.end-datetime .time').html(hour2+':'+minute2+':'+second2);
}
//获取月份天数
function getDays(moth,year)
{
	var d = 30;
	year = parseInt(year);
	moth = moth.toString();
	switch(moth)
	{
		case '1':
			d=31;
			break;
		case '2':
			if ((year % 4 ===0 && year % 100 !==0)||year % 400 ===0)
			{
				d = 29;
			}
			if (parseInt(year) % 4 !==0)
			{
				d = 28;
			}
			break;
		case '3':
			d=31;
			break;
		case '4':
			d=30;
			break;
		case '5':
			d=31;
			break;
		case '6':
			d=30;
			break;
		case '7':
			d=31;
			break;
		case '8':
			d=31;
			break;
		case '9':
			d=30;
			break;
		case '10':
			d=31;
			break;
		case '11':
			d=30;
			break;
		case '12':
			d=31;
			break;
	}
	return d;
}
//获取星期几
function getWeek(date_)
{
	var curDate = date_;	
	//console.log(curDate.getFullYear());
	return curDate.getDay();
}

//填充左边选择器
function fillLeftBox(date_)
{
	var year = date_.getFullYear().toString();
	var month = parseInt(date_.getMonth().toString())+1;
	var day  =  date_.getDate();
	//记录年月
	$('.dateTimeSelector-wraper .left').find('.curr-date').attr('year',year);
	$('.dateTimeSelector-wraper .left').find('.curr-date').attr('month',month);
	$('.dateTimeSelector-wraper .left').find('.curr-date').html(year + '年'+ (month>9?month:'0'+month) + '月');
	//上个月天数
	var lastMonthDays = function(){
		if (month===1)
		{
			return getDays('12',(parseInt(year)-1).toString());
		}
		else
		{
			return getDays(month-1,year);
		}
	}();	
	//当月天数
	var days = getDays(month,year);
	//下个月天数
	var nextMonthDays = function(){
		if (month===12)
		{
			return getDays('1',(parseInt(year)+1).toString());
		}
		else
		{
			return getDays(month+1,year);
		}
	}();	
	//获取当月第一天星期几
	var week = getWeek(new Date(date_.getFullYear().toString() + '-' + (month>9?month.toString():'0'+month) + '-' + '01'));
	console.log('月：'+month);
	console.log('天数：'+days);
	console.log('星期：'+week);
	week = (week===0)?7:week;
	//清空日期
	$('.dateTimeSelector-wraper .left').find('.dates').html('');
	var html_ = '<div class="{className_}" >{day}</div>';
	//添加上个月的日期
	var startDay = lastMonthDays - week +1;
	for(var i=1;i<week;i++)
	{
		var obj = {
			day:startDay + i,
			className_:'date'
		};
		obj.className_ += ' disabled';
		$('.dateTimeSelector-wraper .left .dates').append(formatTemplate(obj,html_));
	}
	//添加这个月的日期
	for(var i=1;i<=days;i++)
	{
		var obj = {
			day:i,
			className_:'date'
		};
		if (i===date_.getDate())
		{
			obj.className_ += ' actived';
		}
		else
		{
			if (obj.className_.indexOf(' actived')===-1)
			{
				if (i===days)
				{
					if ($('.dateTimeSelector-wraper .left .dates .actived').length===0)
					{
						obj.className_ += ' actived';	
					}					
				}
			}			
		}
		$('.dateTimeSelector-wraper .left .dates').append(formatTemplate(obj,html_));
	}
	//添加下个月的日期
	var fillNum = 42 - week + 1 - days;
	for(var i=1;i<=fillNum;i++)
	{
		var obj = {
			day:i,
			className_:'date'
		};
		obj.className_ += ' disabled';
		$('.dateTimeSelector-wraper .left .dates').append(formatTemplate(obj,html_));
	}
}
//填充右边选择器
function fillRightBox(date_)
{
	var year = date_.getFullYear().toString();
	var month = parseInt(date_.getMonth().toString())+1;
	var day  =  date_.getDate();
	//记录年月
	$('.dateTimeSelector-wraper .right').find('.curr-date').attr('year',year);
	$('.dateTimeSelector-wraper .right').find('.curr-date').attr('month',month);
	$('.dateTimeSelector-wraper .right').find('.curr-date').html(year + '年'+ (month>9?month:'0'+month) + '月');
	//上个月天数
	var lastMonthDays = function(){
		if (month===1)
		{
			return getDays('12',(parseInt(year)-1).toString());
		}
		else
		{
			return getDays(month-1,year);
		}
	}();	
	//当月天数
	var days = getDays(month,year);
	//下个月天数
	var nextMonthDays = function(){
		if (month===12)
		{
			return getDays('1',(parseInt(year)+1).toString());
		}
		else
		{
			return getDays(month+1,year);
		}
	}();	
	//获取当月第一天星期几
	var week = getWeek(new Date(date_.getFullYear().toString() + '-' + (month>9?month.toString():'0'+month) + '-' + '01'));
	console.log('月：'+month);
	console.log('星期：'+week);
	week = (week===0)?7:week;
	//清空日期
	$('.dateTimeSelector-wraper .right').find('.dates').html('');
	var html_ = '<div class="{className_}" >{day}</div>';
	//添加上个月的日期
	var startDay = lastMonthDays - week +1;
	for(var i=1;i<week;i++)
	{
		var obj = {
			day:startDay + i,
			className_:'date'
		};
		obj.className_ += ' disabled';
		$('.dateTimeSelector-wraper .right .dates').append(formatTemplate(obj,html_));
	}
	//添加这个月的日期
	for(var i=1;i<=days;i++)
	{
		var obj = {
			day:i,
			className_:'date'
		};
		if (i===date_.getDate())
		{
			obj.className_ += ' actived';
		}
		else
		{
			if (obj.className_.indexOf(' actived')===-1)
			{
				if (i===days)
				{
					if ($('.dateTimeSelector-wraper .right .dates .actived').length===0)
					{
						obj.className_ += ' actived';	
					}					
				}
			}			
		}
		$('.dateTimeSelector-wraper .right .dates').append(formatTemplate(obj,html_));
	}
	//添加下个月的日期
	var fillNum = 42 - week + 1 - days;
	for(var i=1;i<=fillNum;i++)
	{
		var obj = {
			day:i,
			className_:'date'
		};
		obj.className_ += ' disabled';
		$('.dateTimeSelector-wraper .right .dates').append(formatTemplate(obj,html_));
	}
}
function fillTimeSelector()
{
	$('.dateTimeSelector-wraper .left .time .clock').html('');
	$('.dateTimeSelector-wraper .right .time .clock').html('');
	$('.dateTimeSelector-wraper .left .time .minute').html('');
	$('.dateTimeSelector-wraper .right .time .minute').html('');
	$('.dateTimeSelector-wraper .left .time .second').html('');
	$('.dateTimeSelector-wraper .right .time .second').html('');
	for (var i=0;i<=23;i++)
	{
		var html_ = '<option value="'+i+'">'+i+'</option>';
		if (i<10)
		{
			html_ = '<option value="0'+i+'">0'+i+'</option>';
		}
		$('.dateTimeSelector-wraper .left .time .clock').append(html_);
		$('.dateTimeSelector-wraper .right .time .clock').append(html_);
	}	
	for (var i=0;i<=59;i++)
	{
		var html_ = '<option value="'+i+'">'+i+'</option>';
		if (i<10)
		{
			html_ = '<option value="0'+i+'">0'+i+'</option>';
		}
		$('.dateTimeSelector-wraper .left .time .minute').append(html_);
		$('.dateTimeSelector-wraper .right .time .minute').append(html_);
		$('.dateTimeSelector-wraper .left .time .second').append(html_);
		$('.dateTimeSelector-wraper .right .time .second').append(html_);
	}	
	$('.dateTimeSelector-wraper .right .time .clock').val('23');
	$('.dateTimeSelector-wraper .right .time .minute').val('59');
	$('.dateTimeSelector-wraper .right .time .second').val('59');
}
function dateAdd(days=1,dateStr=false)
{
	var date;
	if (!dateStr)
	{
		date = new Date();
		date = date.getFullYear().toString()+'-'+(date.getMonth()+1).toString()+'-'+date.getMonth().getDate().toString();
	}
	dateStr += ' 00:00:00';
	date = Date.parse(new Date(dateStr))/1000;
	date += (86400)*days;
	var newDate = new Date(parseInt(date)*1000);
	return newDate.getFullYear()+'-'+newDate.getMonth()+'-'+newDate.getDate();
}