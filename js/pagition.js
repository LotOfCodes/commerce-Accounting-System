// JavaScript Document
var currpage = 1;

$(document).ready(function(){
	pagitionUI();
	//点击页码
	$(document).on('click','.pages li',function(){
		'use strict';
		if (!$(this).hasClass('actived')&&!$(this).hasClass('disabled')&&!$(this).hasClass('pagination-more'))
		{
			$('.pages li').each(function(){
				$(this).removeClass('actived');
			});
			$(this).addClass('actived');
			var pagenum = $(this).html();
			currpage = isNaN(parseInt(pagenum))?1:parseInt(pagenum);
			//getkdlist(currpage);
			pagitionUI();
		}	
	});
});

//翻页交互
function pagitionUI()
{
	'use strict';
	//记录数量
	var totalnum_str = $('#totalnum').attr('total');
	var totalnum_ = isNaN(parseInt(totalnum_str))?0:parseInt(totalnum_str);
	var showrow = isNaN(parseInt($('#showrow').val()))?0:parseInt($('#showrow').val());
	var totalpage = Math.ceil(totalnum_/showrow);
	console.log('totalnum_:'+totalnum_);
	console.log('showrow:'+showrow);
	console.log('totalpage:'+totalpage);
	console.log('currpage:'+currpage);
	$('.pages').html('');
	var tmplate= '<li class="{className__}" >{numstr}</li>';	
	//小于等于7的情况
	if (totalpage<=7)
	{
		for (var i=1;i<=totalpage;i++)
		{
			var obj ={
				className__:'num',
				numstr:i
			};
			if (i===currpage)
			{
				obj.className__ += ' actived';
			}
			$('.pages').append(formatTemplate(obj,tmplate));
		}
	}
	//等于8的情况
	if (totalpage===8)
	{
		if (currpage<=4)
		{
			for (var i=1;i<=5;i++)
			{
				var obj ={
					className__:'num',
					numstr:i
				};
				if (i===currpage)
				{
					obj.className__ += ' actived';
				}
				$('.pages').append(formatTemplate(obj,tmplate));
			}
			$('.pages').append('<li class="pagination-more">...</li>');
			$('.pages').append('<li class="num">'+totalpage+'</li>');
		}
		//currpage 5
		if (currpage===5)
		{
			$('.pages').append('<li class="num">1</li>');
			$('.pages').append('<li class="pagination-more">...</li>');			
			for (var i=currpage-2;i<=currpage+2;i++)
			{
				var obj ={
					className__:'num',
					numstr:i
				};
				if (i===currpage)
				{
					obj.className__ += ' actived';
				}
				$('.pages').append(formatTemplate(obj,tmplate));
			}
			$('.pages').append('<li class="pagination-more">...</li>');
			$('.pages').append('<li class="num">'+totalpage+'</li>');
		}
		//currpage >5
		if (currpage>5)
		{
			$('.pages').append('<li class="num">1</li>');
			$('.pages').append('<li class="pagination-more">...</li>');			
			for (var i=totalpage-4;i<=totalpage;i++)
			{
				var obj ={
					className__:'num',
					numstr:i
				};
				if (i===currpage)
				{
					obj.className__ += ' actived';
				}
				$('.pages').append(formatTemplate(obj,tmplate));
			}
			$('.pages').append('<li class="pagination-more">...</li>');
			$('.pages').append('<li class="num">'+totalpage+'</li>');
		}
	}
	//大于8的情况
	if (totalpage>8)
	{
		if (currpage<=4)
		{
			for (var i=1;i<=5;i++)
			{
				var obj ={
					className__:'num',
					numstr:i
				};
				if (i===currpage)
				{
					obj.className__ += ' actived';
				}
				console.log('content:'+formatTemplate(obj,tmplate));
				$('.pages').append(formatTemplate(obj,tmplate));
			}
			$('.pages').append('<li class="pagination-more">...</li>');
			$('.pages').append('<li class="num">'+totalpage+'</li>');
		}
		//currpage 5
		if (currpage===5)
		{
			$('.pages').append('<li class="num">1</li>');
			$('.pages').append('<li class="pagination-more">...</li>');			
			for (var i=currpage-2;i<=currpage+2;i++)
			{
				var obj ={
					className__:'num',
					numstr:i
				};
				if (i===currpage)
				{
					obj.className__ += ' actived';
				}
				$('.pages').append(formatTemplate(obj,tmplate));
			}
			$('.pages').append('<li class="pagination-more">...</li>');
			$('.pages').append('<li class="num">'+totalpage+'</li>');
		}
		//currpage >5 <=totalpage-4
		if (currpage>5&&currpage<=totalpage-4)
		{
			$('.pages').append('<li class="num">1</li>');
			$('.pages').append('<li class="pagination-more">...</li>');			
			for (var i=currpage-2;i<=currpage+2;i++)
			{
				var obj ={
					className__:'num',
					numstr:i
				};
				if (i===currpage)
				{
					obj.className__ += ' actived';
				}
				$('.pages').append(formatTemplate(obj,tmplate));
			}
			$('.pages').append('<li class="pagination-more">...</li>');
			$('.pages').append('<li class="num">'+totalpage+'</li>');
		}
		//currpage >5 <totalpage-4
		if (currpage>totalpage-4&&currpage<=totalpage)
		{
			$('.pages').append('<li class="num">1</li>');
			$('.pages').append('<li class="pagination-more">...</li>');			
			for (var i=totalpage-4;i<=totalpage;i++)
			{
				var obj ={
					className__:'num',
					numstr:i
				};
				if (i===currpage)
				{
					obj.className__ += ' actived';
				}
				$('.pages').append(formatTemplate(obj,tmplate));
			}
		}
	}
	//
}