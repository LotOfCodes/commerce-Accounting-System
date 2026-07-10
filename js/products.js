//获取产品
function getProducts()
{
	'use strict';
	var postdata = {};
	var Gr = JSON.parse(localStorage.getItem("orders-token") || "{}");
	var headers= {
		'Content-Type': 'application/json;charset=UTF-8',
		'token': Gr.token ? Gr.token : ""
	};
	var url ='api/getProducts/index.php';
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

//兼容旧调用：父产品已合并为产品。
function getParentProducts()
{
	'use strict';
	return getProducts();
}