<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="ru">
	<head>
		<title>Конструктор данных</title>
		<link rel="stylesheet" href="css/style.css">
		<script src="js/jquery-3.5.1.min.js"></script>
		<script src="https://telegram.org/js/telegram-web-app.js"></script>
		<script src="js/main.js"></script>
		<script src="js/event-listener.js"></script>
		<script src="js/render.js"></script>
		<script src="js/api.js"></script>
		<script src="js/cart.js"></script>
	</head>
	<body>
		<div class="app_bgr"></div>
		<div id="app_container">
			<header>
				<div class="header-actions">
					<div class="main_title_button" id="add_supplier_btn">Поставщики</div>
					<div class="main_title_cart"><img src="media/icons/cart.png" alt=""></div>
				</div>
				<div class="flex_row main_menu_row" id="main_menu">
					<div class="menu_item" cat-id=0>
						<div class="menu_item_img"><img src="media/images/menu/0.png" alt="">
						</div>
						<div class="menu_item_name">Сириус</div>
					</div>
					<!-- Категории будут загружены сюда динамически -->
				</div>
			</header>
			<div class="flex_row main_table_row" id="main_table">
				
			</div>
		</div>
		<div class="popup" id="popup">
			<div class="popup_inner"></div>
		</div>
		<div class="tip">
			Подсказка
		</div>
		<script src="js/ready.js"></script>
		<script src="js/supplier.js"></script>
	</body>
</html>