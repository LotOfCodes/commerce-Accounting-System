# 订单记账管理系统

这是一个基于 PHP + MySQL 的订单记账管理后台，用于管理订单、发货内容、产品计费、商家绑定、快递资费、快递对账、撕单拦截、账单生成和财务统计。

## 目录说明

- `index.html`：入口页，会自动跳转到 `adminPanel.html`
- `adminPanel.html`：管理后台主页面
- `login.html`：管理员登录页
- `pages/`：后台各功能页面
- `api/`：后端接口目录
- `api/ini.example.php`：配置文件示例
- `api/ini.php`：本地运行配置文件，不建议提交到代码仓库
- `数据库.sql`：主数据库初始化脚本
- `sql/`：增量数据库脚本

## 环境要求

- PHP 7.4+，推荐 PHP 8.x
- MySQL 5.7+ 或 MariaDB
- Web 服务器：Apache、Nginx、IIS 或 PHP 内置服务器
- PHP 扩展：`mysqli`

## 部署步骤

1. 将项目放到 Web 服务器站点目录。

2. 创建 MySQL 数据库，例如：

```sql
CREATE DATABASE orders DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
```

3. 导入数据库结构：

```bash
mysql -u your_mysql_user -p orders < 数据库.sql
```

如是旧版本升级，再按需执行 `sql/` 目录中的增量脚本。

4. 创建本地配置文件：

```bash
cp api/ini.example.php api/ini.php
```

5. 修改 `api/ini.php`：

```php
class ini
{
	public $mySqlUser = "your_mysql_user";
	public $mySqlPass = "your_mysql_password";
	public $mySqlDataBase = "orders";
	public $mySqlServer = "127.0.0.1";
	public $authorization = "your_api_token";
	public $initialAdminUser = "admin";
	public $initialAdminPass = "change_this_initial_admin_password";
}
```

配置说明：

- `mySqlUser`：数据库用户名
- `mySqlPass`：数据库密码
- `mySqlDataBase`：数据库名
- `mySqlServer`：数据库地址
- `authorization`：业务 API 初始 token
- `initialAdminUser`：首次登录管理员账号
- `initialAdminPass`：首次登录管理员密码，必须改成自己的密码

`api/ini.php` 内含数据库密码和 token，请作为本地私有配置保存。

## 启动和访问

使用 Web 服务器访问项目根目录即可：

```text
http://你的域名或IP/
```

也可以直接访问后台：

```text
http://你的域名或IP/adminPanel.html
```

本地临时调试可用 PHP 内置服务器：

```bash
php -S 127.0.0.1:8000
```

然后访问：

```text
http://127.0.0.1:8000/
```

## 首次登录

1. 打开 `login.html` 或项目根目录。
2. 使用 `api/ini.php` 中的 `initialAdminUser` 和 `initialAdminPass` 登录。
3. 如果 `admins` 表为空，系统会在首次登录成功时创建管理员账号。
4. 登录后进入「系统设置」，修改管理员密码。
5. 在「系统设置」中查看、保存或删除 API Token。

## 后台使用方法

### 订单列表

- 按时间范围查询订单。
- 支持分页、订单号、快递单号、商品 SKU、手机号等条件检索。
- 点击订单可查看对应发货内容。

### 产品列表

- 维护产品名称、匹配规则、商家绑定、售价、商家价、偏远价、包装费、重量等计费信息。
- 产品可绑定一个或多个商家。
- 商品匹配和账单计算会使用这里的产品配置。

### 商家管理

- 新增、编辑、删除商家。
- 商家可绑定多个店铺编号或店铺名称。
- 订单中的 `customer`、`shopName` 会用于匹配商家。

### 快递资费

- 维护快递公司、模板编码、快递匹配规则、区域计费规则、结算对象、运费承担方等。
- 快递对账和账单核算会使用这里的资费配置。

### 快递对账

- 新建快递对账任务。
- 对比实际快递费用和系统预估费用。
- 支持查看异常、偏差和明细。

### 撕单拦截

- 维护已撕单或需拦截的运单号。
- 可记录快递公司、动作类型、动作时间和备注。

### 生成账单

- 选择商家和账期生成账单。
- 系统会根据订单、发货内容、产品配置、快递资费、付款和欠款记录计算账单。

### 账单列表

- 查看已生成账单。
- 支持账单核对、分享查看和删除。

### 财务统计

- 维护商家付款和欠款。
- 查看财务汇总、已收、未收、欠款等统计信息。

### 系统设置

- 修改管理员密码。
- 查看、保存、复制或删除业务 API Token。

## API 调用规则

除管理员登录相关接口外，业务接口统一使用：

- 请求方法：`POST`
- 请求头：`Content-Type: application/json;charset=UTF-8`
- 请求头：`token: <API_TOKEN>`
- 请求体：JSON

通用响应格式：

```json
{
  "success": true
}
```

失败时通常返回：

```json
{
  "success": false,
  "txt": "错误信息"
}
```

### 健康检查

```bash
curl -X POST http://127.0.0.1:8000/api/checkServer/index.php \
  -H "Content-Type: application/json;charset=UTF-8" \
  -H "token: your_api_token" \
  -d '{"timestamp": 1720000000}'
```

### 新增或更新订单

接口：

```text
POST /api/addOrder/index.php
```

单条订单示例：

```json
{
  "customer": "M001",
  "orderId": "ORDER202607110001",
  "platId": "PLAT202607110001",
  "orderType": "1",
  "tradeStatus": "已发货",
  "shopName": "示例店铺",
  "receiverName": "张三",
  "receiverProvince": "广东",
  "receiverAddress": "广州市天河区示例地址",
  "receiverMobile": "13800000000",
  "remark": "",
  "waybillNumber": "YT123456789",
  "waybillCom": "圆通快递",
  "waybillTemplate": "YT-DEFAULT-2026",
  "deliveryTime": "2026-07-11 10:00:00",
  "printTime": "2026-07-11 10:05:00"
}
```

批量订单示例：

```json
{
  "orders": [
    {
      "customer": "M001",
      "orderId": "ORDER202607110001",
      "tradeStatus": "已发货",
      "deliveryTime": "2026-07-11 10:00:00"
    },
    {
      "customer": "M001",
      "orderId": "ORDER202607110002",
      "tradeStatus": "已发货",
      "deliveryTime": "2026-07-11 10:10:00"
    }
  ]
}
```

批量导入时，已存在的 `orderId` 会更新订单状态，不存在的订单会新增。

### 查询订单

接口：

```text
POST /api/getOrders/index.php
```

请求示例：

```json
{
  "startTime": "2026-07-01 00:00:00",
  "endTime": "2026-07-31 23:59:59",
  "page": 1,
  "pageSize": 50,
  "merchantId": 1,
  "keywordType": "express",
  "keyword": "YT123"
}
```

常用筛选字段：

- `startTime`、`endTime`：发货时间范围
- `page`、`pageSize`：分页参数，`pageSize` 最大 500
- `merchantId` / `merchant`：商家筛选
- `shopName`：店铺筛选
- `status`：订单状态
- `orderType`：订单类型
- `keywordType`：`express`、`goods`、`mobile` 或留空
- `keyword`：关键字

### 新增或更新发货内容

接口：

```text
POST /api/addBean/index.php
```

示例：

```json
{
  "beans": [
    {
      "customer": "M001",
      "parentOrderId": "ORDER202607110001",
      "orderId": "ITEM202607110001",
      "parentPlatId": "PLAT202607110001",
      "platId": "PLATITEM202607110001",
      "orderType": "1",
      "shopName": "示例店铺",
      "tradeStatus": "已发货",
      "sku": "SKU-001",
      "picUrl": "",
      "total": "2",
      "weightActual": "1.25",
      "deliveryTime": "2026-07-11 10:00:00"
    }
  ]
}
```

### 查询发货内容

接口：

```text
POST /api/getBeans/index.php
```

示例：

```json
{
  "orderId": "ORDER202607110001"
}
```

### 产品接口

- `POST /api/getProducts/index.php`：查询产品
- `POST /api/addProduct/index.php`：新增产品
- `POST /api/updateProduct/index.php`：更新产品，需要 `id`
- `POST /api/delProduct/index.php`：删除产品，需要 `id`

新增产品示例：

```json
{
  "productName": "示例产品",
  "merchantIds": "1,2",
  "matchRule": "SKU-001",
  "price": "29.90",
  "mPrice": "20.00",
  "remotePrice": "5.00",
  "packPrice": "1.00",
  "expressPayer": "商家承担",
  "weight": "1.25",
  "startTime": "2026-07-01 00:00:00",
  "endTime": "2026-12-31 23:59:59"
}
```

### 常用接口列表

订单：

- `/api/addOrder/index.php`
- `/api/updateOrder/index.php`
- `/api/getOrders/index.php`
- `/api/delOrder/index.php`

发货内容：

- `/api/addBean/index.php`
- `/api/updateBean/index.php`
- `/api/getBeans/index.php`
- `/api/delBean/index.php`

产品：

- `/api/addProduct/index.php`
- `/api/updateProduct/index.php`
- `/api/getProducts/index.php`
- `/api/delProduct/index.php`

商家：

- `/api/addMerchant/index.php`
- `/api/updateMerchant/index.php`
- `/api/getMerchants/index.php`
- `/api/delMerchant/index.php`

快递资费：

- `/api/addExpress/index.php`
- `/api/updateExpress/index.php`
- `/api/getExpresses/index.php`
- `/api/delExpress/index.php`

快递对账：

- `/api/addExpressAudit/index.php`
- `/api/getExpressAudits/index.php`
- `/api/getExpressAuditDetail/index.php`
- `/api/updateExpressAuditItem/index.php`
- `/api/delExpressAudit/index.php`

撕单拦截：

- `/api/getTearOrders/index.php`
- `/api/updateTearOrder/index.php`
- `/api/delTearOrder/index.php`

账单：

- `/api/addBill/index.php`
- `/api/getBills/index.php`
- `/api/checkBill/index.php`
- `/api/getBillShare/index.php`
- `/api/delBill/index.php`

财务：

- `/api/getFinanceSummary/index.php`
- `/api/addPayment/index.php`
- `/api/updatePayment/index.php`
- `/api/delPayment/index.php`
- `/api/addDebt/index.php`
- `/api/updateDebt/index.php`
- `/api/delDebt/index.php`

管理员：

- `/api/adminLogin/index.php`
- `/api/adminLogout/index.php`
- `/api/adminSettings/index.php`
- `/api/updateAdminPassword/index.php`
- `/api/updateApiToken/index.php`
- `/api/deleteApiToken/index.php`

管理员接口使用 `admin-token` 请求头，业务接口使用 `token` 请求头。

## 数据导入建议

推荐导入顺序：

1. 商家和店铺绑定
2. 产品和匹配规则
3. 快递资费模板
4. 订单
5. 发货内容
6. 付款、欠款和账单

这样生成账单和财务统计时，订单可以正确匹配商家、产品和快递费用。

## 安全注意事项

- 部署后立即修改初始管理员密码。
- 定期更换 API Token。
- 不要将 `api/ini.php` 提交到代码仓库。
- 生产环境建议关闭 PHP 错误显示，改为写入日志。
- 建议仅允许可信系统调用业务 API。
