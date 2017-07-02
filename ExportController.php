<?php
namespace users\modules\orders\controllers;

/*use bupy7\xml\constructor\XmlConstructor;*/
use users\modules\orders\models\Orders;
use users\modules\orders\models\OrdersUser;
use users\modules\orders\models\OrdersUserIds;
use users\modules\orders\models\OrdersBus;
use users\modules\orders\models\OrdersHotel;
use users\modules\orders\models\OrdersServices;
use webvimark\modules\UserManagement\models\Profile;
use webvimark\modules\UserManagement\models\User;
use Yii;
/*use yii\web\Response;*/
use yii\web\Controller;
/*use users\components\Controller;
use users\models\OrdersChangeLog;
use users\models\search\OrdersChangeLogSearch;*/
use yii\helpers\VarDumper;

class ExportController extends Controller
{

    public $order_count = 0;
    public $page = 0;

    //public $freeAccessActions = ['index'];

    public function actionExportOption()
    {

        return $this->renderAjax('/default/_export_opt'/*,compact('model','modelOrder')*/);
    }

    public function actionXmlExportMo43()
    {
        ini_set("memory_limit", "128M");
        /*Yii::$app->response->format = Response::FORMAT_XML;
        Yii::$app->response->formatters['xml'] = ['class' => 'yii\web\XmlResponseFormatter', 'rootTag' => 'ВыгрузкаЗаявок'];*/
        //header("Content-Type: text/xml");
        $minId = Yii::$app->db->createCommand('SELECT id FROM ' . Orders::tableName() . ' ORDER BY id ASC LIMIT 1')->queryOne();
        $maxId = Yii::$app->db->createCommand('SELECT id FROM ' . Orders::tableName() . ' ORDER BY id DESC LIMIT 1')->queryOne();
        $fileSave = false;
        $curDate = Yii::$app->formatter->asDatetime(time());
        if (!empty($minId) && !empty($maxId)) {
            $minId = $minId['id'];
            $maxId = $maxId['id'];
            $filename = Yii::$app->controller->action->id . '-' . $minId . '-' . $maxId . '.xml';
            $fileDir = Yii::getAlias('@users/web/uploads/export/');
            $fileUrl = Yii::$app->urlManager->createUrl('/uploads/export/' . $filename);
            $filePath = $fileDir . $filename;
            if ((file_exists($filePath))) {
                $output = file_get_contents($filePath);
            } else {
                $fileSave = true;
            }
        }
        unset($minId);
        unset($maxId);
        if ($fileSave === true) {
            //$modelOrders = Orders::find()->all();
            $this->order_count = (new \yii\db\Query())->select('*')->from(Orders::tableName())->count();
            $this->page = intval($this->order_count) / 100;
            //VarDumper::dump(intval($this->order_count),100,true);

            /*$fp = fopen($filePath, "w");*/
            $output = '<?xml version="1.0" encoding="UTF-8"?>';
            $output .= '<ВыгрузкаЗаявок ДатаФормирования="' . $curDate . '">';

            $output .= '<Статусы>';
            $output .= '<Статус><Код>'.Orders::STATUS_UNCONFIRMED.'</Код><Наименование>Неподтвержденно</Наименование></Статус>';
            $output .= '<Статус><Код>'.Orders::STATUS_CONFIRMED.'</Код><Наименование>Подтверждено</Наименование></Статус>';
            $output .= '<Статус><Код>'.Orders::STATUS_PRE_BRON.'</Код><Наименование>Предварительная бронь</Наименование></Статус>';
            $output .= '<Статус><Код>'.Orders::STATUS_PRE_PAID.'</Код><Наименование>Предоплачено</Наименование></Статус>';
            $output .= '<Статус><Код>'.Orders::STATUS_PAID.'</Код><Наименование>Оплачено</Наименование></Статус>';
            $output .= '<Статус><Код>'.Orders::STATUS_ANNULLED.'</Код><Наименование>Аннулировано</Наименование></Статус>';
            $output .= '</Статусы>';

            $output .= '<Льготы>';
            $output .= '<Льгота><Код>0</Код><Наименование>нет льгот</Наименование></Льгота>';
            $output .= '<Льгота><Код>1</Код><Наименование>ребёнок</Наименование></Льгота>';
            $output .= '<Льгота><Код>2</Код><Наименование>студент бронь</Наименование></Льгота>';
            $output .= '<Льгота><Код>3</Код><Наименование>пенсионер</Наименование></Льгота>';
            $output .= '</Льготы>';

            /*fwrite($fp, $output);*/
            unset($output);

            if( intval($this->order_count)>0 ) {
                /*fclose($fp);*/
                $output = '';
                echo memory_get_usage().'<br>';
                $this->genUserProfile($filePath);

                echo memory_get_usage().'<br>';
                /*$fp_temp = fopen($filePath, "a");*/
                $output = '<Туристы>';
                /*fwrite($fp_temp, $output);
                fclose($fp_temp);*/
                for($i=1;$i<=$this->page;$i++) {
                    $temp = $this->genTourist($filePath,$i);
                    unset($temp);
                }
                /*$fp_temp = fopen($filePath, "a");*/
                $output = '</Туристы>';
                /*fwrite($fp_temp, $output);
                fclose($fp_temp);*/
                $output = '';
                echo memory_get_usage().'<br>';

                /*$fp_temp = fopen($filePath, "a");*/
                $output = '<ТрансферЗаявок>';
                /*fwrite($fp_temp, $output);
                fclose($fp_temp);*/
                for($i=1;$i<=$this->page;$i++) {
                    $temp = $this->genBus($filePath,$i);
                    unset($temp);
                }
                /*$fp_temp = fopen($filePath, "a");*/
                $output = '</ТрансферЗаявок>';
                /*fwrite($fp_temp, $output);
                fclose($fp_temp);*/
                $output = '';
                echo memory_get_usage().'<br>';

                /*$fp_temp = fopen($filePath, "a");*/
                $output = '<Проживание>';
                /*fwrite($fp_temp, $output);
                fclose($fp_temp);*/
                for($i=1;$i<=$this->page;$i++) {
                    $temp = $this->genHotel($filePath,$i);
                    unset($temp);
                }
                /*$fp_temp = fopen($filePath, "a");*/
                $output = '</Проживание>';
                /*fwrite($fp_temp, $output);
                fclose($fp_temp);*/
                $output = '';
                echo memory_get_usage().'<br>';

                /*$fp_temp = fopen($filePath, "a");*/
                $output = '<Услуги>';
                /*fwrite($fp_temp, $output);
                fclose($fp_temp);*/
                for($i=1;$i<=$this->page;$i++) {
                    $temp = $this->genServices($filePath,$i);
                    unset($temp);
                }
                /*$fp_temp = fopen($filePath, "a");*/
                $output = '</Услуги>';
                /*fwrite($fp_temp, $output);
                fclose($fp_temp);*/
                $output = '';
                echo memory_get_usage().'<br>';

                $this->genOrder($filePath);
            }
            /*$fp = fopen($filePath, "a");*/
            $output .= '</ВыгрузкаЗаявок>';

            /*fwrite($fp, $output);*/

            //if(!empty($output)) {
            //file_put_contents($filePath, $output);
            //$fp = fopen($filePath, "w");

            // записываем в файл текст
            //fwrite($fp, $output);

            // закрываем
            //fclose($fp);
            //}

            /*fclose($fp);*/
        }
        if(!empty($output)) {
            //echo $output;
            unset($output);
            return \yii\helpers\Json::encode(array('url'=>$fileUrl,'filename'=>$filename));
        }
    }

    /*public function custom_put_contents($source_url='',$local_path='')
    {

        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');

        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $remote_contents=file_get_contents($source_url);
        $response=file_put_contents($local_path, $remote_contents);

        set_time_limit($time_limit);
        ini_set('memory_limit', $memory_limit);

        return $response;
    }*/

    public function getPrivilege($data)
    {
        $age = OrdersUser::getAgeYears($data);
        $date_privilege = $data['privilege'];
        $i = intval($age);
        if(empty($date_privilege)) {
            if($i<=14) $privilege = 1;
            //elseif($i<=25) $privilege = 2;
            elseif($i>=55) $privilege = 3;
            else $privilege = 0;
        } else {
            $privilege = $date_privilege;
        }
        return $privilege;
    }

    public function genUserProfile($filePath)
    {
        $return = '';
        $arrOwners = array();
        $i = 1;
        /*$fp_temp = fopen($filePath, "a");*/
        $modelOrders = (new \yii\db\Query())->select(['id','user_id'])->from(Orders::tableName())->all();
        foreach ($modelOrders as $item) {
            //$modelUser = User::find()->where(['id'=>$item['user_id']])->all();
            $modelUser = (new \yii\db\Query())->select(['email','user_parent_id'])->from(User::tableName())->where(['id'=>$item['user_id']])->one();
            $modelUserProfile = (new \yii\db\Query())->select(['phone','company_name','surname','firstname','patronymic'])->from(Profile::tableName())->where(['user_id'=>$item['user_id']])->one();
            if(!empty($modelUser)) {
                if (!empty($modelUser['user_parent_id'])) {
                    $modelUserParent = (new \yii\db\Query())->select(['id', 'email'])->from(User::tableName())->where(['id' => $modelUser['user_parent_id']])->one();
                    $modelUserParentProfile = (new \yii\db\Query())->select(['phone','company_name'])->from(Profile::tableName())->where(['user_id' => $modelUser['user_parent_id']])->one();
                }

                $arrOwners[$i] = [
                    'id' => $item['user_id']
                    ,'email' => $modelUser['email']
                    ,'phone' => $modelUserProfile['phone']
                ];
                if (!empty($modelUserProfile['company_name']) || !empty($modelUser['user_parent_id'])) {
                    if (!empty($modelUser['user_parent_id'])) {//VarDumper::dump($modelUserParentProfile['company_name'],100,true);
                        $arrOwners[$i]['name'] = $modelUserParentProfile['company_name'];
                    } else {
                        $arrOwners[$i]['name'] = $modelUserProfile['company_name'];
                    }
                    $arrOwners[$i]['type'] = 'Турагентство';
                } else {
                    $arrOwners[$i]['name'] = Profile::getFio($modelUserProfile,true);
                    $arrOwners[$i]['type'] = 'Турист';
                }
            }
            $i++;
            unset($item);
            unset($modelUser);
            unset($modelUserProfile);
            unset($modelUserParent);
            unset($modelUserParentProfile);
        }

        if( count($arrOwners)>0 ) {
            $return .= '<Пользователи>';
            foreach ($arrOwners as $item) {
                $return .= '<Пользователь>';
                $return .= '<Код>'.$item['id'].'</Код>';
                $return .= '<Тип>'.$item['type'].'</Тип>';
                if(!empty($item['name'])) {
                    $return .= '<Наименование>' . $item['name'] . '</Наименование>';
                }
                if( !empty($item['email']) ) {
                    $return .= '<ЭлектронныйАдрес>' . $item['email'] . '</ЭлектронныйАдрес>';
                }
                if( !empty($item['phone']) ) {
                    $return .= '<Телефон>' . $item['phone'] . '</Телефон>';
                }
                $return .= '</Пользователь>';

                /*fwrite($fp_temp, $return);*/
                $return = '';
                unset($item);
            }
            $return .= '</Пользователи>';
            /*fwrite($fp_temp, $return);*/
        }
        /*fclose($fp_temp);*/
        unset($return);
        unset($modelOrders);
        unset($arrOwners);
        return true;
    }

    public function genTourist($filePath,$page)
    {
        $return = '';
        /*$fp_temp = fopen($filePath, "a");*/
        $modelOrders = (new \yii\db\Query())->select(['id'])->from(Orders::tableName())->limit(100)->offset($page*100)->all();
        foreach ($modelOrders as $item) {
            $modelOrdersUserIds = (new \yii\db\Query())->select(['id','tourist_id'])->from(OrdersUserIds::tableName())->where(['order_id'=>$item['id']])->all();
            if( count($modelOrdersUserIds)>0 ) {
                foreach ($modelOrdersUserIds as $item2) {
                    $modelOrdersUser = (new \yii\db\Query())->select('*')->from(OrdersUser::tableName())->where(['id'=>$item2['tourist_id']])->all();
                    $return .= '<Турист>';
                    $return .= '<Код>'.$item2['id'].'</Код>';
                    $return .= '<Заявка>'.$item['id'].'</Заявка>';

                    $return .= '<Льгота>'.$this->getPrivilege($modelOrdersUser).'</Льгота>';
                    $return .= '<Наименование>'.$modelOrdersUser['name'].'</Наименование>';
                    $return .= '<ДеньРождения>'.date('d.m.Y', $modelOrdersUser['birthday']).'</ДеньРождения>';
                    //$return .= '<ДеньРождения>'.Yii::$app->formatter->asDate($modelOrdersUser['birthday']).'</ДеньРождения>';
                    if( !empty($modelOrdersUser['pasport']) ) {
                        $return .= '<Паспорт>' . $modelOrdersUser['pasport'] . '</Паспорт>';
                    }
                    if( !empty($modelOrdersUser['phone']) ) {
                        $return .= '<Телефон>' . $modelOrdersUser['phone'] . '</Телефон>';
                    }
                    $return .= '</Турист>';
                    /*fwrite($fp_temp, $return);*/
                    $return = '';
                    unset($item2);
                    unset($modelOrdersUser);
                }
            }
            unset($item);
            unset($modelOrdersUserIds);
        }
        /*fclose($fp_temp);*/
        unset($return);
        unset($modelOrders);
        return true;
    }

    public function genBus($filePath,$page)
    {
        $return = '';
        /*$fp_temp = fopen($filePath, "a");*/
        $modelOrders = (new \yii\db\Query())->select(['id'])->from(Orders::tableName())->limit(100)->offset($page*100)->all();
        foreach ($modelOrders as $item) {
            //$modelOrdersBus = OrdersBus::find()->where(['order_id'=>$item['id']])->all();
            $modelOrdersBus = (new \yii\db\Query())->select('*')->from(OrdersBus::tableName())->where(['order_id'=>$item['id']])->all();
            $modelOrdersUserIds = (new \yii\db\Query())->select(['id','tourist_id'])->from(OrdersUserIds::tableName())->where(['order_id'=>$item['id']])->all();
            if( count($modelOrdersBus)>0 ) {
                foreach ($modelOrdersBus as $key=>$item2) {
                    $modelOrdersUser = (new \yii\db\Query())->select('*')->from(OrdersUser::tableName())->where(['id'=>$modelOrdersUserIds[$key]['tourist_id']])->all();
                    $return .= '<Трансфер>';
                    $return .= '<Код>'.$item2['id'].'</Код>';
                    $return .= '<Заявка>'.$item['id'].'</Заявка>';
                    if(!empty($modelOrdersUserIds[$key])) {
                        $return .= '<Турист>' . $modelOrdersUser['id'] . '</Турист>';
                        //$return .= '<ТуристВозраст>' . OrdersUser::getAgeYears($modelOrdersUser) . '</ТуристВозраст>';
                    }
                    $return .= '<Льгота>'.$this->getPrivilege($modelOrdersUser).'</Льгота>';
                    $return .= '<Наименование>'.strip_tags($item2['name']).'</Наименование>';
                    $return .= '<Место>'.$item2['place'].'</Место>';
                    $return .= '</Трансфер>';

                    /*fwrite($fp_temp, $return);*/
                    $return = '';
                    unset($key);
                    unset($item2);
                    unset($modelOrdersUser);
                }
            }
            unset($item);
            unset($modelOrdersBus);
            unset($modelOrdersUserIds);
        }
        /*fclose($fp_temp);*/
        unset($return);
        unset($modelOrders);
        return true;
    }

    public function genHotel($filePath,$page)
    {
        $return = '';
        /*$fp_temp = fopen($filePath, "a");*/
        $modelOrders = (new \yii\db\Query())->select(['id'])->from(Orders::tableName())->limit(100)->offset($page*100)->all();
        foreach ($modelOrders as $item) {
            //$modelOrdersHotel = OrdersHotel::find()->where(['order_id'=>$item['id']])->all();
            $modelOrdersHotel = (new \yii\db\Query())->select('*')->from(OrdersHotel::tableName())->where(['order_id'=>$item['id']])->all();
            $modelOrdersUserIds = (new \yii\db\Query())->select(['id','tourist_id'])->from(OrdersUserIds::tableName())->where(['order_id'=>$item['id']])->all();
            if( count($modelOrdersHotel)>0 ) {
                foreach ($modelOrdersHotel as $key=>$item2) {
                    $modelOrdersUser = (new \yii\db\Query())->select('*')->from(OrdersUser::tableName())->where(['id'=>$modelOrdersUserIds[$key]['tourist_id']])->all();
                    $return .= '<Отель>';
                    $return .= '<Код>'.$item2['id'].'</Код>';
                    $return .= '<Заявка>'.$item['id'].'</Заявка>';
                    if(!empty($modelOrdersUserIds[$key])) {
                        $return .= '<Турист>' . $modelOrdersUser['id'] . '</Турист>';
                        //$return .= '<ТуристВозраст>' . OrdersUser::getAgeYears($modelOrdersUser) . '</ТуристВозраст>';
                    }
                    $return .= '<Льгота>'.$this->getPrivilege($modelOrdersUser).'</Льгота>';
                    $return .= '<Наименование>'.strip_tags($item2['name']).'</Наименование>';
                    if( !empty($item2['place']) ) {
                        $return .= '<Размещение>' . $item2['place'] . '</Размещение>';
                    }
                    if( !empty($item2['date_drive']) ) {
                        $return .= '<ДатаЗаезда>' . date('d.m.Y',$item2['date_drive']) . '</ДатаЗаезда>';
                        //$return .= '<ДатаЗаезда>' . Yii::$app->formatter->asDate($item2['date_drive']) . '</ДатаЗаезда>';
                    }
                    if( !empty($item2['place']) ) {
                        $return .= '<Срок>' . $item2['place'] . '</Срок>';
                    }
                    if( !empty($item2['count']) ) {
                        $return .= '<Количество>' . $item2['count'] . '</Количество>';
                    }
                    //$return .= '<Цена>'.$item2['price'].'</Цена>';
                    $return .= '</Отель>';

                    /*fwrite($fp_temp, $return);*/
                    $return = '';
                    /*unset($key);
                    unset($item2);*/
                    unset($modelOrdersUser);
                }

                /*fwrite($fp_temp, $return);*/
                $return = '';
            }
            unset($item);
            unset($modelOrdersHotel);
            unset($modelOrdersUserIds);
        }
        /*fclose($fp_temp);*/
        unset($return);
        unset($modelOrders);
        return true;
    }

    public function genServiceItem($filePath, $order_id)
    {
        $return = '';
        $modelOrdersServices = (new \yii\db\Query())->select('*')->from(OrdersServices::tableName())->where(['order_id'=>$order_id])->all();
        //$modelOrdersServices = OrdersServices::find()->where(['order_id'=>$order_id])->all();
        if(isset($modelOrdersServices)) {
            if( count($modelOrdersServices)>0 ) {
                /*$fp_temp = fopen($filePath, "a");*/
                foreach ($modelOrdersServices as $item2) {
                    $return .= '<Услуга>';
                    $return .= '<Код>'.$item2['id'].'</Код>';
                    $return .= '<Заявка>'.$order_id.'</Заявка>';
                    $return .= '<Наименование>'. $item2['name'] .'</Наименование>';
                    $return .= '<Количество>'.$item2['count'].'</Количество>';
                    $return .= '<Цена>'.$item2['price'].'</Цена>';
                    $return .= '</Услуга>';

                    /*fwrite($fp_temp, $return);*/
                    unset($item2);
                    $return = '';
                }

                /*fclose($fp_temp);*/
            }
            unset($modelOrdersServices);
        }
        unset($return);
        return true;
    }

    public function genServiceHotel($filePath, $order_id)
    {
        $return = '';
        $modelOrdersHotel = (new \yii\db\Query())->select('*')->from(OrdersHotel::tableName())->where(['order_id'=>$order_id])->all();
        //$modelOrdersHotel = OrdersHotel::find()->where(['order_id'=>$order_id])->all();
        if(isset($modelOrdersHotel)) {
            if( count($modelOrdersHotel)>0 ) {
                /*$fp_temp = fopen($filePath, "a");*/
                foreach ($modelOrdersHotel as $item2) {
                    $return .= '<Услуга>';
                    $return .= '<Код>'.$item2['id'].'</Код>';
                    $return .= '<Заявка>'.$order_id.'</Заявка>';
                    $return .= '<Наименование>'.strip_tags($item2['name']).'</Наименование>';
                    $return .= '<Количество>'.$item2['count'].'</Количество>';
                    $return .= '<Цена>'.$item2['price'].'</Цена>';
                    $return .= '</Услуга>';

                    /*fwrite($fp_temp, $return);*/
                    unset($item2);
                    $return = '';
                }

                /*fclose($fp_temp);*/
            }
            unset($modelOrdersHotel);
        }
        unset($return);
        return true;
    }

    public function genServices($filePath,$page)
    {
        $return = '';
        /*$fp_temp = fopen($filePath, "a");*/
        $modelOrders = (new \yii\db\Query())->select(['id'])->from(Orders::tableName())->limit(100)->offset($page*100)->all();
        foreach ($modelOrders as $key=>$item) {
            $this->genServiceItem($filePath,$item['id']);
            $this->genServiceHotel($filePath,$item['id']);
            unset($key);
            unset($item);
        }
        /*fclose($fp_temp);*/
        unset($return);
        unset($modelOrders);
        return true;
    }

    public function genOrder($filePath)
    {
        for($i=1;$i<=$this->page;$i++) {
            $this->genOrderItem($filePath,$i);
        }
    }

    public function genOrderItem($filePath,$page)
    {
        $return = '';
        /*$fp_temp = fopen($filePath, "a");*/
        $return .= '<Заявки>';
        $modelOrders = (new \yii\db\Query())->select('*')->from(Orders::tableName())->limit(100)->offset($page*100)->all();
        foreach ($modelOrders as $item) {
            $return .= '<Заявка>';
            $return .=
                '<Код>'.$item['id'].'</Код>
                            <Пользователь>'.$item['user_id'].'</Пользователь>
                            <ДатаСоздания>'.Yii::$app->formatter->asDatetime($item['created_at']).'</ДатаСоздания>';
            if( $item['type_order'] == 1 ) {
                $return .= '<Авиабилет>Да</Авиабилет>';
            } else {
                $return .= '<Авиабилет>Нет</Авиабилет>';
            }
            $return .='<Статус>'.$item['status'].'</Статус>';
            $return .='<НаименованиеТура>'.$item['name_tour'].'</НаименованиеТура>';
            $return .='<ДатаОтправки>'.date('d.m.Y',$item['date_departure']).'</ДатаОтправки>';
            //$return .='<ДатаОтправки>'.Yii::$app->formatter->asDate($item['date_departure']).'</ДатаОтправки>';
            //$return .='<ДатаОтправки>'.date('d-m-Y',$item['date_departure']).'</ДатаОтправки>';
            if( !empty($item['price']) ) {
                $return .= '<Цена>' . $item['price'] . '</Цена>';
            }
            if( !empty($item['price_end']) ) {
                $return .= '<Оплачено>' . $item['price_end'] . '</Оплачено>';
            }
            if( !empty($item['comment']) ) {
                $return .= '<Уточнения>' . strip_tags($item['comment']) . '</Уточнения>';
            }
            if( !empty($item['typepay']) ) {
                $return .= '<ФормаОплаты>' . Orders::getTypepay($item['typepay']) . '</ФормаОплаты>';
            }
            if( !empty($item['tourcitystart']) ) {
                $return .= '<ГородВыезда>'.$item['tourcitystart'].'</ГородВыезда>';
            }
            if( !empty($item['tourcityend']) ) {
                $return .= '<ГородПрибытия>'.$item['tourcityend'].'</ГородПрибытия>';
            }
            $side = Orders::getSide($item['side']);
            if( !empty($side) ) {
                $return .= '<Направление>' . $side . '</Направление>';
            }
            $return .= '</Заявка>';

            /*fwrite($fp_temp, $return);*/
            unset($item);
            $return = '';
        }
        $return .= '</Заявки>';
        /*fwrite($fp_temp, $return);
        fclose($fp_temp);*/
        unset($return);
        unset($modelOrders);
        return true;
    }
}