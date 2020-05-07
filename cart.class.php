<?php
/**
*		Корзина товаров для фудмикс70.рф
*
*		@link          https://gfwe.ru
*		@author        gfwe.ru <info@gfwe.ru>
*		@copyright     Copyright (c) 2020, Siven.Kolenz
*/
	
	@error_reporting(E_ALL^E_WARNING^E_DEPRECATED^E_NOTICE);
	@ini_set('error_reporting', E_ALL^E_WARNING^E_DEPRECATED^E_NOTICE);

	@ini_set('display_errors', true);
	@ini_set('html_errors', false);
	
	define ( 'DEBUG', false );

	function pre( $var ) {	
		echo '<pre>';
		print_r( $var );
		echo '</pre>';
	}

	class Cart {

		private $_is_ajax = false;	// Работа корзины возможна только через AJAX запросы.
		private $_cart = [];	// Локальная копия данных корзины
		private $_code = '';	// Артикул текущего товара
		private $_date = '';	// Дата на какое число собираем меню.

		private function info ( $func_name ) {	// Вспомогательная функция для определения последовательности выполнения функций.
			if ( DEBUG ) {
				if ( !isset( $this->_f_count ) )
					$this->_f_count = 0;
				$this->_f_count++;
				echo "#{$this->_f_count}	Func `".$func_name."` started<br>";
			}
		}

		public function __construct ( $code, $action ) {	// Точка ввода для данных и запуска работы корзины.
			$this->info( '__construct' );
			if( $this->is_ajax() || DEBUG ) {
				$this->check_session();
				$this->load_cart();
				$this->set_code( $code );
				$this->set_action( $action );
				$this->start_action();
				$this->save_cart();
				$this->return_cart();
			} else {
				die( "This cart works only use AJAX\n<br>" );
			}
		}

		private function is_ajax () {	// Проверяем был ли запрос через AJAX
			$this->info( 'is_ajax' );
			$this->_is_ajax = ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ? true : false );
			return $this->_is_ajax;
		}

		private function check_session () {	// Проверка на наличие сессии с данными корзины
			$this->info( 'check_session' );
			if ( isset( $_SESSION['selected_data'] ) ) {
				$this->_date = $_SESSION['selected_data'];
			} else {
				$this->_date = date( 'd.m' );
			}

			if( !isset( $_SESSION['cart'][$this->_date] ) ) {
				$_SESSION['cart'][$this->_date] = [
					'code' => [],
					'item' => [],
					'count' => 0,
					'amount' => 0,
					'error' => false,
				];
			}
		}

		private function load_cart () {	// Загрузка данных корзины из сессии
			$this->info( 'load_cart' );
			$this->_cart = $_SESSION['cart'][$this->_date];
		}

		private function save_cart () {	// Сохранение данных корзины в сессию]
			$this->info( 'save_cart' );
			$_SESSION['cart'][$this->_date] = $this->_cart;
		}

		private function set_date ( $date ) {	// Установка артикула товара для последующей работы с товаром в корзине
			$this->info( 'set_date' );
			$this->_date = $code;
		}
		private function set_code ( $code ) {	// Установка артикула товара для последующей работы с товаром в корзине
			$this->info( 'set_code' );
			if ( empty( $code ) ) {
				die( "Vendor code can't be empty!\n<br>" );
			} else {
				$this->_code = $code;
			}
		}

		private function set_action ( $action ) {	// Устанавливаем действие корзины для дальнейшей работы
			$this->info( 'set_action' );
			if ( empty( $action ) ) {
				die("Error:\n<br>	\$action is empty\n<br>");
			} else {
				$this->_action = $action;
			}
		}

		private function start_action () {	// Функция запуска действия корзины
			$this->info( 'start_action' );
			switch ( $this->_action ) {
				case 'add':
					$this->add_item();
					break;

				case 'plus':
					$this->add_item();
					break;

				case 'minus':
					$this->count_minus();
					break;

				case 'delete':
					$this->delete_item();
					break;

				case 'clear':
					$this->clear_cart();
					break;

				case 'return':
					
					break;
				
				default:
					die( "Action `{$this->_action}` is not defined" );
					break;
			}
		}

		private function check_code () { // Функция для проверки наличия товара в корзине
			$this->info( 'check_code' );
			if ( in_array( $this->_code, $this->_cart['code'] ) ) {
				return true;
			} else {
				return false;
			}
		}

		private function count_plus () {	// Функция увеличения кол-ва товара в корзине
			$this->info( 'count_plus' );
			$this->_cart['item'][$this->_code]['count']++;
		}

		private function count_minus () {	// Функция уменьшения кол-ва товара в корзине
			$this->info( 'count_minus' );

			if ( $this->check_code() ) {
				if ( $this->_cart['item'][$this->_code]['count'] == 1 ) {
					$this->delete_item();
					$this->recalc_global_amount();
				} else {
					$this->_cart['item'][$this->_code]['count']--;	
					$this->recalc_amount();
				}
			} else {
				die( "Item `{$this->_code}` not found!\n<br>" );
			}
		}

		private function create_new_item () {	// Функция создания нового товара в корзине
			$this->info( 'create_new_item' );
			global $db;
			$this->_cart['code'][] = $this->_code;

			$db->query( "SELECT `name`, `price`, `price_sale` FROM `".dbtable( 'food' )."` WHERE `article` = '{$db->safesql( $this->_code )}';" );
			$row = $db->get_row();
			if ( !empty( $row['price_sale'] ) ) $row['price'] = $row['price_sale'];
			if ( !isset( $row['name'] ) ) {
				die( "Item `{$this->_code}` not found!" );
			} else {
				$this->_cart['item'][$this->_code] = [
					'name' => $row['name'],
					'price' => $row['price'],
					'count' => 1,
					'sum' => 0,
				];
			}
		}

		private function delete_item () {	// Функция удаления товара из корзины
			$this->info( 'delete_item' );
			unset( $this->_cart['item'][$this->_code] );
			foreach ($this->_cart['code'] as $key => $artcle) {
				if ( $artcle == $this->_code ) {
					unset( $this->_cart['code'][$key] );
				}
			}
			$this->recalc_global_amount();
		}

		private function clear_cart () {	// Функция очистки корзины
			$this->info( 'clear_cart' );
			unset( $this->_cart );
		}

		private function recalc_amount () {	// Функция пересчета суммы товара
			$this->info( 'recalc_amount' );
			$this->_cart['item'][$this->_code]['sum'] = $this->_cart['item'][$this->_code]['count'] * $this->_cart['item'][$this->_code]['price'];
			$this->recalc_global_amount();
		}

		private function recalc_global_amount () {	// Функция пересчета суммы всей корзины
			$this->info( 'recalc_global_amount' );
			$this->_cart['amount'] = 0;
			$this->_cart['count'] = 0;
			foreach ($this->_cart['item'] as $key => $item) {
				$this->_cart['amount'] += $item['sum'];
				$this->_cart['count']++;
			}
		}

		private function add_item () {	// Функция добавления товара
			$this->info( 'add_item' );
			if ( $this->check_code() ) {
				$this->count_plus();
			} else {
				$this->create_new_item ();
			}
			$this->recalc_amount();
		}

		private function return_cart ( $send_order = false ) {	// Функция возвращения данных корзины в JSON формате
			$this->info( 'return_cart' );

			if ( $send_order ) {
				return $this->_cart;
			} else {
				$json = json_encode( $this->_cart );
				if ( DEBUG )
					pre( json_decode( $json ) );
				else 
					echo $json;
			}
		}

		public function __destruct () {	// Точка выхода/завершения работы с корзиной
			$this->info( '__destruct' );
			// $this->save_cart();
			// $this->return_cart();
		}
		
	}

?>