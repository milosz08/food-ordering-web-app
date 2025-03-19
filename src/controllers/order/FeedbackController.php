<?php

namespace App\Controllers\Order;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Order\FeedbackService;

ResourceLoader::load_service('FeedbackService', 'Order');

class FeedbackController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(FeedbackService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /order/feedback/give-feedback
   */
  public function give_feedback()
  {
    $this->protector->protect_only_user();
    $feedback_data = $this->_service->give_a_mark_for_restaurant();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::FEEDBACK_GIVE_FEEDBACK_PAGE_BANNER);
    $this->renderer->render('user/add-edit-feedback-view', array(
      'page_title' => 'Wystaw ocenę',
      'page_text' => 'Wystaw',
      'data' => $feedback_data,
      'banner' => $banner_data,
    ));
  }

  /**
   * Przejście pod adres: /order/feedback/edit-feedback
   */
  public function edit_feedback()
  {
    $this->protector->protect_only_user();
    $feedback_data = $this->_service->edit_mark_for_restaurant();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::FEEDBACK_EDIT_FEEDBACK_PAGE_BANNER);
    $this->renderer->render('user/add-edit-feedback-view', array(
      'page_title' => 'Edytuj ocenę',
      'data' => $feedback_data,
      'banner' => $banner_data,
    ));
  }

  /**
   * Przejście pod adres: /order/feedback/edit-feedback. Proxy pod adres /user/orders.order-details
   */
  public function delete_feedback()
  {
    $this->protector->protect_only_user();
    $order_id = $this->_service->delete_mark_from_restaurant();
    header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-details?id=' . $order_id, true, 301);
  }

  /**
   * Przejście pod adres: /order/feedback. Proxy pod adres /user/orders
   */
  public function index()
  {
    $this->protector->protect_only_user();
    header('Location:' . __URL_INIT_DIR__ . 'user/orders', true, 301);
  }
}
