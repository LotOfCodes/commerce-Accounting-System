//获取父产品
function getParentProducts()
{
	'use strict';
	var postdata = {"orderId":orderId};
	var Gr = JSON.parse(localStorage.getItem("orders-token") || "{}"); 
	var headers= {  
		'Content-Type': 'application/json;charset=UTF-8',  
		'token': Gr.token ? Gr.token : ""
	}; 
	var url ='https://orders.8866.info/api/getParentProducts/index.php';	
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
//获取产品
function getProducts()
{
	'use strict';
	var postdata = {"orderId":orderId};
	var Gr = JSON.parse(localStorage.getItem("orders-token") || "{}"); 
	var headers= {  
		'Content-Type': 'application/json;charset=UTF-8',  
		'token': Gr.token ? Gr.token : ""
	}; 
	var url ='https://orders.8866.info/api/getProducts/index.php';	
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