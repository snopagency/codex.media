<?php

class Model_Feed_Abstract extends Model
{
    protected $redis;
    protected $prefix;
    protected $pin_limit = 1;

    protected $timeline_key = null;

    /**
     * Timeline constructor.
     * При создании экземпляра класса можно передать префикс для хранения в фиде различных сущностей.
     * По умолчанию префикс отсутствует.
     *
     * @param string $prefix
     */
    public function __construct($prefix = '')
    {
        $this->redis = Controller_Base_preDispatch::_redis();

        $this->prefix = $prefix;
    }

    /**
     * Получаем идентефикатор, с учетом типа (префикса) элемента
     *
     * @param $id
     *
     * @return string
     */
    public function composeValueIdentity($id)
    {
        if (!$this->prefix) {
            return $id;
        }

        return $this->prefix . ':' . $id;
    }

    /**
     * Разбиваем идентефикатор на префих и id
     *
     * @param string $identity
     *
     * @return array - массив из двух элементов (prefix, id)
     */
    public function decomposeValueIdentity($identity)
    {
        return explode(':', $identity);
    }

    /**
     * Меняем порядок элементов в фиде
     *
     * @param $item_id - id элемента, который переставляем
     * @param $item_below_value - value элемента,
     * после которого в sorted set вставляем $item (перед которым $item будет выводиться)
     *
     * @return string|bool - false при ошибке или новое значение score, представленное в строке
     */
    public function putAbove($item_id, $item_below_value)
    {
        $item_value = $this->composeValueIdentity($item_id);

        if ($this->redis->zRank($this->timeline_key, $item_value) === false) {
            return false;
        }

        if ($this->redis->zRank($this->timeline_key, $item_below_value) === false) {
            return false;
        }

        $interval = $this->redis->zScore($this->timeline_key, $item_below_value) - $this->redis->zScore($this->timeline_key, $item_value);

        return $this->redis->zIncrBy($this->timeline_key, $interval + 1, $item_value);
    }

    /**
     * Добавляем элемент в фид.
     * Идентефикатор сформируется с учетом префикса текущего экземпляра класса
     *
     * @param int $item_id
     * @param int $item_score
     *
     * @return bool|int
     */
    public function add($item_id, $item_score = null)
    {
        $item_score = $item_score ?: strtotime("now");

        $value = $this->composeValueIdentity($item_id);

        if ($this->redis->zRank($this->timeline_key, $value) !== false) {
            return false;
        }

        return $this->redis->zAdd($this->timeline_key, $item_score, $value);
    }

    /**
     * Удаляем элемент из фида
     *
     * @param $item_id
     */
    public function remove($item_id)
    {
        $value = $this->composeValueIdentity($item_id);

        $this->redis->zRem($this->timeline_key, $value);

        if ($this->isPinned($item_id)) {
            $pinned = $this->getPinnedFeed();
            $pinned->remove($item_id);
        }
    }

    /**
     * Получаем индентефикаторы первых $numberOfItems элементов в фиде,
     * сделав отступ в $offset элементов (для пагинации).
     * Если $numberOfItems не указано, то получаем весь фид.
     *
     * @param int $numberOfItems
     * @param int $offset
     *
     * @return array - массив идентефикаторов элементов
     */
    public function get($numberOfItems = 0, $offset = 0)
    {
        $stop = $numberOfItems + $offset;

        $stop = $this->redis->zCard($this->timeline_key) > $stop ? $stop : 0;

        $stop = $numberOfItems ? $numberOfItems + $offset : 0;

        $items = $this->redis->zRevRange($this->timeline_key, $offset, $stop - 1);

        return $items;
    }

    /**
     * Метод для первой инициализации фида.
     *
     * @param {array} $items -  ассоциативный массив идентефикаторов элементов с датой создания(поля 'id' и 'dt_create')
     */
    public function init($items)
    {
        foreach ($items as $item) {
            $this->add($item['id'], $item['dt_create']);
        }
    }

    /**
     * Очистить фид
     */
    public function clear()
    {
        $this->redis->del($this->timeline_key);
    }

    /**
     * Поиск значения в фидах
     *
     * @param mixed $item_id
     */
    public function isExist($item_id)
    {
        $item_id = $this->composeValueIdentity($item_id);

        return $this->redis->zRank($this->timeline_key, $item_id) !== false;
    }

    /**
     * Закрепляет запись с $item_id в фиде
     *
     * @param $item_id
     */
    public function pin($item_id)
    {
        if (!$this->isExist($item_id)) {
            return;
        }

        $firstItem = $this->redis->zRevRange($this->timeline_key, 0, 0, true);
        $score = array_pop($firstItem);

        $this->redis->zIncrBy($this->timeline_key, $score, $this->composeValueIdentity($item_id));

        $pinned = $this->getPinnedFeed();

        if ($pinned->length() >= $this->pin_limit) {
            $oldest = $this->redis->zRange($pinned->timeline_key, 0, 0)[0];
            $this->unpin($oldest);
        }

        $pinned->add($item_id, $score);
    }

    /**
     * Открепляет запись с $item_id
     *
     * @param $item_id
     */
    public function unpin($item_id)
    {
        if (!$this->isExist($item_id)) {
            return;
        }

        $pinned = $this->getPinnedFeed();

        if (!$pinned->isExist($item_id)) {
            return;
        }

        $this->redis->zIncrBy($this->timeline_key, -$pinned->getScore($item_id), $this->composeValueIdentity($item_id));

        $pinned->remove($item_id);
    }

    /**
     * Меняет состояние записи (откреплена/закреплена) в фиде
     *
     * @param $item_id
     */
    public function togglePin($item_id)
    {
        if ($this->isPinned($item_id)) {
            $this->unpin($item_id);

            return;
        }

        $this->pin($item_id);
    }

    /**
     * Возвращает true, если запись закреплена
     *
     * @param $item_id
     *
     * @return bool
     */
    public function isPinned($item_id)
    {
        $pinned = $this->getPinnedFeed();

        return $pinned->isExist($item_id);
    }

    /**
     * Возвращает количество элементов в фиде
     *
     * @return int
     */
    public function length()
    {
        return $this->redis->zCount($this->timeline_key, '-inf', '+inf');
    }

    /**
     * Возвращает модель фида для закрпеленных записей
     *
     * @return Model_Feed_Abstract
     */
    private function getPinnedFeed()
    {
        $pinned = new self($this->prefix);
        $pinned->timeline_key = $this->timeline_key . '-pinned';

        return $pinned;
    }

    /**
     * Получает значение score для записи $item_id
     *
     * @param $item_id
     *
     * @return float
     */
    public function getScore($item_id)
    {
        $item_id = $this->composeValueIdentity($item_id);

        return $this->redis->zScore($this->timeline_key, $item_id);
    }
}
