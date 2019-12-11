<?php

namespace App\Services;

class BaseApiClient
{
    /**
     * Получение результата curl запроса
     *
     * @param cURL $curl экземпляр класса cURl
     * @return array массив ответа сервера
     * - data (array) - массив данных ответа сервера
     * - error (array) - ошибки
     * - info (array) - вспомогательная информация
     */
    public function curlResult($curl)
    {
        $result = array(
            'data' => curl_exec($curl),
            'error' => curl_error($curl),
            'info' => curl_getinfo($curl)
        );

        $result['data'] = json_decode($result['data'], true);

        return $result;
    }

    /**
     * POST запрос
     *
     * @param string $url ссылка запроса
     * @param array $data данные запроса
     * @param array $header заголовки
     * @param bool $ssl
     *
     * @return array массив ответа сервера
     * - data (array) - массив данных ответа сервера
     * - error (array) - ошибки
     * - info (array) - вспомогательная информация
     */
    public function post($url, $data, $header = [], $ssl = false)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $ssl);

        $result = $this->curlResult($curl);

        curl_close($curl);

        return $result;
    }

    /**
     * GET запрос
     *
     * @param string $url ссылка запроса
     * @param array $header заголовки
     * @return array массив ответа сервера
     * - data (array) - массив данных ответа сервера
     * - error (array) - ошибки
     * - info (array) - вспомогательная информация
     */
    public function get($url, $header = [])
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $result = $this->curlResult($curl);

        curl_close($curl);

        return $result;
    }

    /**
     * PUT запрос
     *
     * @param string $url ссылка запроса
     * @param array $data данные запроса
     * @param array $header заголовки
     * @return array массив ответа сервера
     * - data (array) - массив данных ответа сервера
     * - error (array) - ошибки
     * - info (array) - вспомогательная информация
     */
    public function put($url, $data, $header = [])
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $result = $this->curlResult($curl);

        curl_close($curl);

        return $result;
    }

    /**
     * DELETE запрос
     *
     * @param string $url ссылка запроса
     * @param array $data данные запроса
     * @param array $header заголовки
     * @return array массив ответа сервера
     * - data (array) - массив данных ответа сервера
     * - error (array) - ошибки
     * - info (array) - вспомогательная информация
     */
    public function delete($url, $data, $header = [])
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $result = $this->curlResult($curl);

        curl_close($curl);

        return $result;
    }

    /**
     * Успешный запрос к API
     *
     * @param array $data массив данных полученных из API
     * @return array массив данных + _status true
     */
    public function success($data)
    {
        $data['_status'] = true;

        return $data;
    }

    /**
     * Неудачный запрос к API
     *
     * @param array $data массив данных полученных из API
     * @return array массив данных + _status false
     */
    public function fail($data)
    {
        $data['_status'] = false;

        return $data;
    }

    /**
     * Добавляем параметры в url из массива
     * - если есть конструкции param={param}, то будет произведена замена {param} на зачение
     * - если нет - параметр будет добавлен в конец ссылки
     *
     * @param string $url ссылка
     * @param array $param массив параметров для добавления
     * @return string ссылка с параметрами
     */
    public function addUrlParam($url, $param)
    {
        if (empty($param)) {
            return $url;
        }

        //последний символ ссылки
        if (!$this->strFind($url, '?')) {
            $last = '?';
        } else {
            $last = '&';
        }

        //параметры добавляемые в конеч
        $after = [];

        //смотрим все параметры
        foreach ($param as $key => $value) {
            //если параметр обязательный, вставляем его в нужное место
            if ($this->strFind($url, '{' . $key . '}')) {
                $url = $this->strReplace('{' . $key . '}', $value, $url);

                //иначе помещаем в конец
            } else {
                $after[] = $key . '=' . $value;
            }
        }

        //если есть параметры для помещения в конец ссылки
        if (!empty($after)) {
            $url .= $last . implode('&', $after);
        }

        return $url;
    }

    /**
     * Регистронезависимый поиск подстроки в строке
     *
     * @param string $haystack где искать
     * @param string $needle что искать
     *
     * @return bool
     */
    protected function strFind($haystack, $needle)
    {
        if (empty($needle)) {
            return false;
        }

        return mb_stripos($haystack, $needle, 0, 'utf-8') !== false;
    }

    /**
     * Замена подстроки в строке (mb_str_replace)
     *
     * @param string $needle подстрока для замены
     * @param string $replacement текст замены
     * @param string $haystack строка для поиска
     *
     * @return string результирующая строка
     */
    protected function strReplace($needle, $replacement, $haystack)
    {
        return implode($replacement, mb_split(addcslashes($needle, '()[]?.*+{}|'), $haystack));
    }
}
