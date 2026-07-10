var orderList,beanList;
var ordersPageSize = 50;
var ordersTotal = 0;
$(document).ready(function(){	
	'use strict';
	//搜索栏
	$('#search-type').change(function(){
		var searchtype = $(this).val();
		$('.orderid').hide();
		$('.expressnum').hide();
		$('.goodsnum-start').hide();
		$('.goodsnum-end').hide();
		$('.goodsnum-center').hide();
		$('.mobile').hide();
		switch(searchtype)
		{
			case "all":
				break;
			case "orderid":
				$('.orderid').show();
				break;
			case "expressnum":
				$('.expressnum').show();
				break;
			case "goodsnum":
				$('.goodsnum-start').show();
				$('.goodsnum-end').show();
				$('.goodsnum-center').show();
				break;
			case "mobile":
				$('.mobile').show();
				break;
		}
	});
	//点击查询
	$('#search').click(function(){
		getOrders(getStartTime(),getEndTime(),1,getOrdersPageSize());
	});
	$('#showrow').change(function(){
		ordersPageSize = getOrdersPageSize();
		getOrders(getStartTime(),getEndTime(),1,ordersPageSize);
	});
});
//获取订单
function getOrders(startTime,endTime,page,pageSize)
{
	'use strict';
	pageSize = parseInt(pageSize || getOrdersPageSize(),10);
	pageSize = pageSize > 0 ? pageSize : 50;
	var postdata = {"page":page,"pageSize":pageSize,"startTime":startTime,"endTime":endTime};
	var Gr = JSON.parse(localStorage.getItem("orders-token") || "{}"); 
	var headers= {  
		'Content-Type': 'application/json;charset=UTF-8',  
		'token': Gr.token ? Gr.token : ""
	}; 
	var url = 'api/getOrders/index.php';	
	fetch(url, {
		method: 'POST',
		headers: headers,
		body: JSON.stringify(postdata),
		//mode: 'cors',
		//credentials: 'include',
		redirect: 'follow'
	}).then(function(res){
		return res.json();
	}).then(function(data){		
		if (data.success)
		{
			orderList = data.data;
			ordersTotal = parseInt(data.total || data.data.length || 0,10);
			ordersPageSize = parseInt(data.pageSize || pageSize,10);
			$('#totalnum').attr('total',ordersTotal).text('共 ' + ordersTotal + ' 条');
			doOrders(data.data);
		}		
	});
}
function getOrdersPageSize()
{
	'use strict';
	var pageSize = parseInt($('#showrow').val(),10);
	return pageSize > 0 ? pageSize : ordersPageSize;
}
//获取beans
function getBeans(orderId)
{
	'use strict';
	var postdata = {"orderId":orderId};
	var Gr = JSON.parse(localStorage.getItem("orders-token") || "{}"); 
	var headers= {  
		'Content-Type': 'application/json;charset=UTF-8',  
		'token': Gr.token ? Gr.token : ""
	}; 
	var url ='api/getBeans/index.php';	
	fetch(url, {
		method: 'POST',
		headers: headers,
		body: JSON.stringify(postdata),
		mode: 'cors',
		credentials: 'include',
		redirect: 'follow'
	}).then(function(res){
		return res.json();
	}).then(function(data){		
		if (data.success)
		{
			beanList = data.data;
			doBeans(data.data);
		}		
	});
}
function doOrders(orders){
	'use strict';
	if (orders && Array.isArray(orders)) {
		orders.forEach(order=>{
			var htmlstr = $('.order-tr-template').html();
			var orderId = order.orderId;
			var tr_content = formatTemplate(order,htmlstr);
			$('#orders-list').append(tr_content);
			getBeans(orderId);
		});	
	}	
}
function doBeans(beans){
	'use strict';
	if (beans && Array.isArray(beans)) {
		beans.forEach(bean=>{		
			$('#orders-list tr').each(function(){
				var orderId_tr = $(this).attr('orderid');
				if(orderId_tr===bean.parentOrderId)
				{
					var htmlstr = $('.bean-template').html();
					var tr_content = formatTemplate(bean,htmlstr);
					$(this).find('.beans').append(tr_content);
				}
			});
		});	
	}	
}
function getStartTime()
{
	'use strict';
	var class__ = $('.start-datetime');
	var year = class__.find('.date').attr('year');
	var month = parseInt(class__.find('.date').attr('month'))>9?class__.find('.date').attr('month'):'0'+class__.find('.date').attr('month');
	var day = parseInt(class__.find('.date').attr('day'))>9?class__.find('.date').attr('day'):'0'+class__.find('.date').attr('day');
	var hour = class__.find('.time').attr('hour');
	var minute = class__.find('.time').attr('minute');
	var second = class__.find('.time').attr('second');
	return year+'-'+month+'-'+day+' '+hour+':'+minute+':'+second;
}
function getEndTime()
{
	'use strict';
	var class__ = $('.end-datetime');
	var year = class__.find('.date').attr('year');
	var month = parseInt(class__.find('.date').attr('month'))>9?class__.find('.date').attr('month'):'0'+class__.find('.date').attr('month');
	var day = parseInt(class__.find('.date').attr('day'))>9?class__.find('.date').attr('day'):'0'+class__.find('.date').attr('day');
	var hour = class__.find('.time').attr('hour');
	var minute = class__.find('.time').attr('minute');
	var second = class__.find('.time').attr('second');
	return year+'-'+month+'-'+day+' '+hour+':'+minute+':'+second;
}
