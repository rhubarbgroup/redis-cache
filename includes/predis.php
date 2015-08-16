<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * Defines an abstraction representing a Redis command.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface CommandInterface
{
    /**
     * Returns the ID of the Redis command. By convention, command identifiers
     * must always be uppercase.
     *
     * @return string
     */
    public function getId();

    /**
     * Assign the specified slot to the command for clustering distribution.
     *
     * @param int $slot Slot ID.
     */
    public function setSlot($slot);

    /**
     * Returns the assigned slot of the command for clustering distribution.
     *
     * @return int|null
     */
    public function getSlot();

    /**
     * Sets the arguments for the command.
     *
     * @param array $arguments List of arguments.
     */
    public function setArguments(array $arguments);

    /**
     * Sets the raw arguments for the command without processing them.
     *
     * @param array $arguments List of arguments.
     */
    public function setRawArguments(array $arguments);

    /**
     * Gets the arguments of the command.
     *
     * @return array
     */
    public function getArguments();

    /**
     * Gets the argument of the command at the specified index.
     *
     * @param int $index Index of the desired argument.
     *
     * @return mixed|null
     */
    public function getArgument($index);

    /**
     * Parses a raw response and returns a PHP object.
     *
     * @param string $data Binary string containing the whole response.
     *
     * @return mixed
     */
    public function parseResponse($data);
}

/**
 * Base class for Redis commands.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class Command implements CommandInterface
{
    private $slot;
    private $arguments = array();

    /**
     * Returns a filtered array of the arguments.
     *
     * @param array $arguments List of arguments.
     *
     * @return array
     */
    protected function filterArguments(array $arguments)
    {
        return $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $this->filterArguments($arguments);
        unset($this->slot);
    }

    /**
     * {@inheritdoc}
     */
    public function setRawArguments(array $arguments)
    {
        $this->arguments = $arguments;
        unset($this->slot);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument($index)
    {
        if (isset($this->arguments[$index])) {
            return $this->arguments[$index];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setSlot($slot)
    {
        $this->slot = $slot;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlot()
    {
        if (isset($this->slot)) {
            return $this->slot;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data;
    }

    /**
     * Normalizes the arguments array passed to a Redis command.
     *
     * @param array $arguments Arguments for a command.
     *
     * @return array
     */
    public static function normalizeArguments(array $arguments)
    {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return $arguments[0];
        }

        return $arguments;
    }

    /**
     * Normalizes the arguments array passed to a variadic Redis command.
     *
     * @param array $arguments Arguments for a command.
     *
     * @return array
     */
    public static function normalizeVariadic(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge(array($arguments[0]), $arguments[1]);
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/zrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetRange extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZRANGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 4) {
            $lastType = gettype($arguments[3]);

            if ($lastType === 'string' && strtoupper($arguments[3]) === 'WITHSCORES') {
                // Used for compatibility with older versions
                $arguments[3] = array('WITHSCORES' => true);
                $lastType = 'array';
            }

            if ($lastType === 'array') {
                $options = $this->prepareOptions(array_pop($arguments));

                return array_merge($arguments, $options);
            }
        }

        return $arguments;
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options List of options.
     *
     * @return array
     */
    protected function prepareOptions($options)
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();

        if (!empty($opts['WITHSCORES'])) {
            $finalizedOpts[] = 'WITHSCORES';
        }

        return $finalizedOpts;
    }

    /**
     * Checks for the presence of the WITHSCORES modifier.
     *
     * @return bool
     */
    protected function withScores()
    {
        $arguments = $this->getArguments();

        if (count($arguments) < 4) {
            return false;
        }

        return strtoupper($arguments[3]) === 'WITHSCORES';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if ($this->withScores()) {
            $result = array();

            for ($i = 0; $i < count($data); ++$i) {
                $result[$data[$i]] = $data[++$i];
            }

            return $result;
        }

        return $data;
    }
}

/**
 * @link http://redis.io/commands/sinterstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetIntersectionStore extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SINTERSTORE';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge(array($arguments[0]), $arguments[1]);
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/eval
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerEval extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EVAL';
    }

    /**
     * Calculates the SHA1 hash of the body of the script.
     *
     * @return string SHA1 hash.
     */
    public function getScriptHash()
    {
        return sha1($this->getArgument(0));
    }
}

/**
 * @link http://redis.io/commands/sinter
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetIntersection extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SINTER';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeArguments($arguments);
    }
}

/**
 * @link http://redis.io/commands/rpush
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPushTail extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'RPUSH';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeVariadic($arguments);
    }
}

/**
 * @link http://redis.io/commands/subscribe
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PubSubSubscribe extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SUBSCRIBE';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeArguments($arguments);
    }
}

/**
 * @link http://redis.io/commands/blpop
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPopFirstBlocking extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BLPOP';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[0])) {
            list($arguments, $timeout) = $arguments;
            array_push($arguments, $timeout);
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/ttl
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyTimeToLive extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'TTL';
    }
}

/**
 * @link http://redis.io/commands/expireat
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyExpireAt extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EXPIREAT';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/rename
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyRename extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'RENAME';
    }
}

/**
 * @link http://redis.io/commands/unsubscribe
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PubSubUnsubscribe extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'UNSUBSCRIBE';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeArguments($arguments);
    }
}

/**
 * @link http://redis.io/commands/zunionstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetUnionStore extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZUNIONSTORE';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        $options = array();
        $argc = count($arguments);

        if ($argc > 2 && is_array($arguments[$argc - 1])) {
            $options = $this->prepareOptions(array_pop($arguments));
        }

        if (is_array($arguments[1])) {
            $arguments = array_merge(
                array($arguments[0], count($arguments[1])),
                $arguments[1]
            );
        }

        return array_merge($arguments, $options);
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options List of options.
     *
     * @return array
     */
    private function prepareOptions($options)
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();

        if (isset($opts['WEIGHTS']) && is_array($opts['WEIGHTS'])) {
            $finalizedOpts[] = 'WEIGHTS';

            foreach ($opts['WEIGHTS'] as $weight) {
                $finalizedOpts[] = $weight;
            }
        }

        if (isset($opts['AGGREGATE'])) {
            $finalizedOpts[] = 'AGGREGATE';
            $finalizedOpts[] = $opts['AGGREGATE'];
        }

        return $finalizedOpts;
    }
}

/**
 * @link http://redis.io/commands/zrangebylex
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetRangeByLex extends ZSetRange
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZRANGEBYLEX';
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareOptions($options)
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();

        if (isset($opts['LIMIT']) && is_array($opts['LIMIT'])) {
            $limit = array_change_key_case($opts['LIMIT'], CASE_UPPER);

            $finalizedOpts[] = 'LIMIT';
            $finalizedOpts[] = isset($limit['OFFSET']) ? $limit['OFFSET'] : $limit[0];
            $finalizedOpts[] = isset($limit['COUNT']) ? $limit['COUNT'] : $limit[1];
        }

        return $finalizedOpts;
    }

    /**
     * {@inheritdoc}
     */
    protected function withScores()
    {
        return false;
    }
}

/**
 * @link http://redis.io/commands/setex
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringSetExpire extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SETEX';
    }
}

/**
 * @link http://redis.io/commands/expire
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyExpire extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EXPIRE';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/info
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerInfo extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'INFO';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        $info = array();
        $infoLines = preg_split('/\r?\n/', $data);

        foreach ($infoLines as $row) {
            if (strpos($row, ':') === false) {
                continue;
            }

            list($k, $v) = $this->parseRow($row);
            $info[$k] = $v;
        }

        return $info;
    }

    /**
     * Parses a single row of the response and returns the key-value pair.
     *
     * @param string $row Single row of the response.
     *
     * @return array
     */
    protected function parseRow($row)
    {
        list($k, $v) = explode(':', $row, 2);

        if (preg_match('/^db\d+$/', $k)) {
            $v = $this->parseDatabaseStats($v);
        }

        return array($k, $v);
    }

    /**
     * Extracts the statistics of each logical DB from the string buffer.
     *
     * @param string $str Response buffer.
     *
     * @return array
     */
    protected function parseDatabaseStats($str)
    {
        $db = array();

        foreach (explode(',', $str) as $dbvar) {
            list($dbvk, $dbvv) = explode('=', $dbvar);
            $db[trim($dbvk)] = $dbvv;
        }

        return $db;
    }

    /**
     * Parses the response and extracts the allocation statistics.
     *
     * @param string $str Response buffer.
     *
     * @return array
     */
    protected function parseAllocationStats($str)
    {
        $stats = array();

        foreach (explode(',', $str) as $kv) {
            @list($size, $objects, $extra) = explode('=', $kv);

            // hack to prevent incorrect values when parsing the >=256 key
            if (isset($extra)) {
                $size = ">=$objects";
                $objects = $extra;
            }

            $stats[$size] = $objects;
        }

        return $stats;
    }
}

/**
 * @link http://redis.io/commands/zrangebyscore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetRangeByScore extends ZSetRange
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZRANGEBYSCORE';
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareOptions($options)
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();

        if (isset($opts['LIMIT']) && is_array($opts['LIMIT'])) {
            $limit = array_change_key_case($opts['LIMIT'], CASE_UPPER);

            $finalizedOpts[] = 'LIMIT';
            $finalizedOpts[] = isset($limit['OFFSET']) ? $limit['OFFSET'] : $limit[0];
            $finalizedOpts[] = isset($limit['COUNT']) ? $limit['COUNT'] : $limit[1];
        }

        return array_merge($finalizedOpts, parent::prepareOptions($options));
    }

    /**
     * {@inheritdoc}
     */
    protected function withScores()
    {
        $arguments = $this->getArguments();

        for ($i = 3; $i < count($arguments); ++$i) {
            switch (strtoupper($arguments[$i])) {
                case 'WITHSCORES':
                    return true;

                case 'LIMIT':
                    $i += 2;
                    break;
            }
        }

        return false;
    }
}

/**
 * @link http://redis.io/commands/mset
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringSetMultiple extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'MSET';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            $flattenedKVs = array();
            $args = $arguments[0];

            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }

            return $flattenedKVs;
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/evalsha
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerEvalSHA extends ServerEval
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EVALSHA';
    }

    /**
     * Returns the SHA1 hash of the body of the script.
     *
     * @return string SHA1 hash.
     */
    public function getScriptHash()
    {
        return $this->getArgument(0);
    }
}

/**
 * @link http://redis.io/commands/decr
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringDecrement extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'DECR';
    }
}

/**
 * @link http://redis.io/commands/decrby
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringDecrementBy extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'DECRBY';
    }
}

/**
 * @link http://redis.io/commands/get
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringGet extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'GET';
    }
}

/**
 * @link http://redis.io/commands/bitpos
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringBitPos extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BITPOS';
    }
}

/**
 * @link http://redis.io/commands/bitop
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringBitOp extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BITOP';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            list($operation, $destination) = $arguments;
            $arguments = $arguments[2];
            array_unshift($arguments, $operation, $destination);
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/append
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringAppend extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'APPEND';
    }
}

/**
 * @link http://redis.io/commands/bitcount
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringBitCount extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BITCOUNT';
    }
}

/**
 * @link http://redis.io/commands/getbit
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringGetBit extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'GETBIT';
    }
}

/**
 * @link http://redis.io/commands/mget
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringGetMultiple extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'MGET';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeArguments($arguments);
    }
}

/**
 * @link http://redis.io/commands/incrbyfloat
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringIncrementByFloat extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'INCRBYFLOAT';
    }
}

/**
 * @link http://redis.io/commands/psetex
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringPreciseSetExpire extends StringSetExpire
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PSETEX';
    }
}

/**
 * @link http://redis.io/commands/set
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringSet extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SET';
    }
}

/**
 * @link http://redis.io/commands/incrby
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringIncrementBy extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'INCRBY';
    }
}

/**
 * @link http://redis.io/commands/incr
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringIncrement extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'INCR';
    }
}

/**
 * @link http://redis.io/commands/getrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringGetRange extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'GETRANGE';
    }
}

/**
 * @link http://redis.io/commands/getset
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringGetSet extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'GETSET';
    }
}

/**
 * @link http://redis.io/commands/sunionstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetUnionStore extends SetIntersectionStore
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SUNIONSTORE';
    }
}

/**
 * @link http://redis.io/commands/sunion
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetUnion extends SetIntersection
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SUNION';
    }
}

/**
 * @link http://redis.io/commands/sdiff
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetDifference extends SetIntersection
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SDIFF';
    }
}

/**
 * @link http://redis.io/commands/sdiffstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetDifferenceStore extends SetIntersectionStore
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SDIFFSTORE';
    }
}

/**
 * @link http://redis.io/commands/hget
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashGet extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HGET';
    }
}

/**
 * @link http://redis.io/commands/scard
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetCardinality extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SCARD';
    }
}

/**
 * @link http://redis.io/commands/sadd
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetAdd extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SADD';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeVariadic($arguments);
    }
}

/**
 * @link http://redis.io/commands/slowlog
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerSlowlog extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SLOWLOG';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_array($data)) {
            $log = array();

            foreach ($data as $index => $entry) {
                $log[$index] = array(
                    'id' => $entry[0],
                    'timestamp' => $entry[1],
                    'duration' => $entry[2],
                    'command' => $entry[3],
                );
            }

            return $log;
        }

        return $data;
    }
}

/**
 * @link http://redis.io/commands/time
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerTime extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'TIME';
    }
}

/**
 * @link http://redis.io/commands/hexists
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashExists extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HEXISTS';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/sismember
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetIsMember extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SISMEMBER';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/srem
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetRemove extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SREM';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeVariadic($arguments);
    }
}

/**
 * @link http://redis.io/commands/sscan
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetScan extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SSCAN';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            $options = $this->prepareOptions(array_pop($arguments));
            $arguments = array_merge($arguments, $options);
        }

        return $arguments;
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options List of options.
     *
     * @return array
     */
    protected function prepareOptions($options)
    {
        $options = array_change_key_case($options, CASE_UPPER);
        $normalized = array();

        if (!empty($options['MATCH'])) {
            $normalized[] = 'MATCH';
            $normalized[] = $options['MATCH'];
        }

        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }

        return $normalized;
    }
}

/**
 * @link http://redis.io/commands/srandmember
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetRandomMember extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SRANDMEMBER';
    }
}

/**
 * @link http://redis.io/commands/spop
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetPop extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SPOP';
    }
}

/**
 * @link http://redis.io/commands/smembers
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetMembers extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SMEMBERS';
    }
}

/**
 * @link http://redis.io/commands/smove
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SetMove extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SMOVE';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/setbit
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringSetBit extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SETBIT';
    }
}

/**
 * @link http://redis.io/commands/hdel
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashDelete extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HDEL';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeVariadic($arguments);
    }
}

/**
 * @link http://redis.io/commands/zrem
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetRemove extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREM';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeVariadic($arguments);
    }
}

/**
 * @link http://redis.io/commands/zremrangebylex
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetRemoveRangeByLex extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREMRANGEBYLEX';
    }
}

/**
 * @link http://redis.io/commands/zremrangebyrank
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetRemoveRangeByRank extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREMRANGEBYRANK';
    }
}

/**
 * @link http://redis.io/commands/zrank
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetRank extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZRANK';
    }
}

/**
 * @link http://redis.io/commands/echo
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionEcho extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ECHO';
    }
}

/**
 * @link http://redis.io/commands/quit
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionQuit extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'QUIT';
    }
}

/**
 * @link http://redis.io/commands/ping
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionPing extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PING';
    }
}

/**
 * @link http://redis.io/commands/zremrangebyscore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetRemoveRangeByScore extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREMRANGEBYSCORE';
    }
}

/**
 * @link http://redis.io/commands/zrevrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetReverseRange extends ZSetRange
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREVRANGE';
    }
}

/**
 * @link http://redis.io/commands/zscore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetScore extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZSCORE';
    }
}

/**
 * @link http://redis.io/commands/auth
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionAuth extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'AUTH';
    }
}

/**
 * @link http://redis.io/commands/zscan
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetScan extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZSCAN';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            $options = $this->prepareOptions(array_pop($arguments));
            $arguments = array_merge($arguments, $options);
        }

        return $arguments;
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options List of options.
     *
     * @return array
     */
    protected function prepareOptions($options)
    {
        $options = array_change_key_case($options, CASE_UPPER);
        $normalized = array();

        if (!empty($options['MATCH'])) {
            $normalized[] = 'MATCH';
            $normalized[] = $options['MATCH'];
        }

        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }

        return $normalized;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_array($data)) {
            $members = $data[1];
            $result = array();

            for ($i = 0; $i < count($members); ++$i) {
                $result[$members[$i]] = (float) $members[++$i];
            }

            $data[1] = $result;
        }

        return $data;
    }
}

/**
 * @link http://redis.io/commands/zrevrank
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetReverseRank extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREVRANK';
    }
}

class ZSetReverseRangeByLex extends ZSetRangeByLex
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREVRANGEBYLEX';
    }
}

/**
 * @link http://redis.io/commands/zrevrangebyscore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetReverseRangeByScore extends ZSetRangeByScore
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREVRANGEBYSCORE';
    }
}

/**
 * @link http://redis.io/commands/zlexcount
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetLexCount extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZLEXCOUNT';
    }
}

/**
 * @link http://redis.io/commands/zinterstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetIntersectionStore extends ZSetUnionStore
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZINTERSTORE';
    }
}

/**
 * @link http://redis.io/commands/strlen
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringStrlen extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'STRLEN';
    }
}

/**
 * @link http://redis.io/commands/substr
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringSubstr extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SUBSTR';
    }
}

/**
 * @link http://redis.io/commands/discard
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class TransactionDiscard extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'DISCARD';
    }
}

/**
 * @link http://redis.io/commands/setrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringSetRange extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SETRANGE';
    }
}

/**
 * @link http://redis.io/commands/setnx
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringSetPreserve extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SETNX';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/select
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionSelect extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SELECT';
    }
}

/**
 * @link http://redis.io/commands/msetnx
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StringSetMultiplePreserve extends StringSetMultiple
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'MSETNX';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/exec
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class TransactionExec extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EXEC';
    }
}

/**
 * @link http://redis.io/commands/multi
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class TransactionMulti extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'MULTI';
    }
}

/**
 * @link http://redis.io/commands/zcount
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetCount extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZCOUNT';
    }
}

/**
 * @link http://redis.io/commands/zincrby
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetIncrementBy extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZINCRBY';
    }
}

/**
 * @link http://redis.io/commands/zcard
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetCardinality extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZCARD';
    }
}

/**
 * @link http://redis.io/commands/zadd
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZSetAdd extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZADD';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (is_array(end($arguments))) {
            foreach (array_pop($arguments) as $member => $score) {
                $arguments[] = $score;
                $arguments[] = $member;
            }
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/unwatch
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class TransactionUnwatch extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'UNWATCH';
    }
}

/**
 * @link http://redis.io/commands/watch
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class TransactionWatch extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'WATCH';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/slaveof
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerSlaveOf extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SLAVEOF';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/shutdown
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerShutdown extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SHUTDOWN';
    }
}

/**
 * @link http://redis.io/commands/hset
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashSet extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HSET';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/type
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyType extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'TYPE';
    }
}

/**
 * @link http://redis.io/commands/lindex
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListIndex extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LINDEX';
    }
}

/**
 * @link http://redis.io/commands/sort
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeySort extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SORT';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 1) {
            return $arguments;
        }

        $query = array($arguments[0]);
        $sortParams = array_change_key_case($arguments[1], CASE_UPPER);

        if (isset($sortParams['BY'])) {
            $query[] = 'BY';
            $query[] = $sortParams['BY'];
        }

        if (isset($sortParams['GET'])) {
            $getargs = $sortParams['GET'];

            if (is_array($getargs)) {
                foreach ($getargs as $getarg) {
                    $query[] = 'GET';
                    $query[] = $getarg;
                }
            } else {
                $query[] = 'GET';
                $query[] = $getargs;
            }
        }

        if (isset($sortParams['LIMIT']) &&
            is_array($sortParams['LIMIT']) &&
            count($sortParams['LIMIT']) == 2) {
            $query[] = 'LIMIT';
            $query[] = $sortParams['LIMIT'][0];
            $query[] = $sortParams['LIMIT'][1];
        }

        if (isset($sortParams['SORT'])) {
            $query[] = strtoupper($sortParams['SORT']);
        }

        if (isset($sortParams['ALPHA']) && $sortParams['ALPHA'] == true) {
            $query[] = 'ALPHA';
        }

        if (isset($sortParams['STORE'])) {
            $query[] = 'STORE';
            $query[] = $sortParams['STORE'];
        }

        return $query;
    }
}

/**
 * @link http://redis.io/commands/scan
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyScan extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SCAN';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $options = $this->prepareOptions(array_pop($arguments));
            $arguments = array_merge($arguments, $options);
        }

        return $arguments;
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options List of options.
     *
     * @return array
     */
    protected function prepareOptions($options)
    {
        $options = array_change_key_case($options, CASE_UPPER);
        $normalized = array();

        if (!empty($options['MATCH'])) {
            $normalized[] = 'MATCH';
            $normalized[] = $options['MATCH'];
        }

        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }

        return $normalized;
    }
}

/**
 * @link http://redis.io/commands/renamenx
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyRenamePreserve extends KeyRename
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'RENAMENX';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/restore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyRestore extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'RESTORE';
    }
}

/**
 * @link http://redis.io/commands/linsert
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListInsert extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LINSERT';
    }
}

/**
 * @link http://redis.io/commands/llen
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListLength extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LLEN';
    }
}

/**
 * @link http://redis.io/commands/rpoplpush
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPopLastPushHead extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'RPOPLPUSH';
    }
}

/**
 * @link http://redis.io/commands/brpoplpush
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPopLastPushHeadBlocking extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BRPOPLPUSH';
    }
}

/**
 * @link http://redis.io/commands/lpush
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPushHead extends ListPushTail
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LPUSH';
    }
}

/**
 * @link http://redis.io/commands/brpop
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPopLastBlocking extends ListPopFirstBlocking
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BRPOP';
    }
}

/**
 * @link http://redis.io/commands/rpop
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPopLast extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'RPOP';
    }
}

/**
 * @link http://redis.io/commands/lpop
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPopFirst extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LPOP';
    }
}

/**
 * @link http://redis.io/commands/hscan
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashScan extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HSCAN';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            $options = $this->prepareOptions(array_pop($arguments));
            $arguments = array_merge($arguments, $options);
        }

        return $arguments;
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options List of options.
     *
     * @return array
     */
    protected function prepareOptions($options)
    {
        $options = array_change_key_case($options, CASE_UPPER);
        $normalized = array();

        if (!empty($options['MATCH'])) {
            $normalized[] = 'MATCH';
            $normalized[] = $options['MATCH'];
        }

        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }

        return $normalized;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_array($data)) {
            $fields = $data[1];
            $result = array();

            for ($i = 0; $i < count($fields); ++$i) {
                $result[$fields[$i]] = $fields[++$i];
            }

            $data[1] = $result;
        }

        return $data;
    }
}

/**
 * @link http://redis.io/commands/hmset
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashSetMultiple extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HMSET';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $flattenedKVs = array($arguments[0]);
            $args = $arguments[1];

            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }

            return $flattenedKVs;
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/randomkey
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyRandom extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'RANDOMKEY';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data !== '' ? $data : null;
    }
}

/**
 * @link http://redis.io/commands/dump
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyDump extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'DUMP';
    }
}

/**
 * @link http://redis.io/commands/exists
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyExists extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EXISTS';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/hstrlen
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashStringLength extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HSTRLEN';
    }
}

/**
 * @link http://redis.io/commands/del
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyDelete extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'DEL';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeArguments($arguments);
    }
}

/**
 * @link http://redis.io/commands/pfmerge
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HyperLogLogMerge extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PFMERGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeArguments($arguments);
    }
}

/**
 * @link http://redis.io/commands/pfadd
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HyperLogLogAdd extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PFADD';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeVariadic($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/pfcount
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HyperLogLogCount extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PFCOUNT';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeArguments($arguments);
    }
}

/**
 * @link http://redis.io/commands/hsetnx
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashSetPreserve extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HSETNX';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/keys
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyKeys extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'KEYS';
    }
}

/**
 * @link http://redis.io/commands/pexpireat
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyPreciseExpireAt extends KeyExpireAt
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PEXPIREAT';
    }
}

/**
 * @link http://redis.io/commands/pttl
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyPreciseTimeToLive extends KeyTimeToLive
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PTTL';
    }
}

/**
 * @link http://redis.io/commands/pexpire
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyPreciseExpire extends KeyExpire
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PEXPIRE';
    }
}

/**
 * @link http://redis.io/commands/persist
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyPersist extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PERSIST';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/migrate
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyMigrate extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'MIGRATE';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (is_array(end($arguments))) {
            foreach (array_pop($arguments) as $modifier => $value) {
                $modifier = strtoupper($modifier);

                if ($modifier === 'COPY' && $value == true) {
                    $arguments[] = $modifier;
                }

                if ($modifier === 'REPLACE' && $value == true) {
                    $arguments[] = $modifier;
                }
            }
        }

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/move
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyMove extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'MOVE';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return (bool) $data;
    }
}

/**
 * @link http://redis.io/commands/lpushx
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPushHeadX extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LPUSHX';
    }
}

/**
 * @link http://redis.io/commands/hlen
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashLength extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HLEN';
    }
}

/**
 * @link http://redis.io/commands/hmget
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashGetMultiple extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HMGET';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        return self::normalizeVariadic($arguments);
    }
}

/**
 * @link http://redis.io/commands/flushall
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerFlushAll extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'FLUSHALL';
    }
}

/**
 * @link http://redis.io/commands/flushdb
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerFlushDatabase extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'FLUSHDB';
    }
}

/**
 * @link http://redis.io/commands/hincrby
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashIncrementBy extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HINCRBY';
    }
}

/**
 * @link http://redis.io/commands/dbsize
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerDatabaseSize extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'DBSIZE';
    }
}

/**
 * @link http://redis.io/commands/command
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'COMMAND';
    }
}

/**
 * @link http://redis.io/commands/config-set
 * @link http://redis.io/commands/config-get
 * @link http://redis.io/commands/config-resetstat
 * @link http://redis.io/commands/config-rewrite
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerConfig extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'CONFIG';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_array($data)) {
            $result = array();

            for ($i = 0; $i < count($data); ++$i) {
                $result[$data[$i]] = $data[++$i];
            }

            return $result;
        }

        return $data;
    }
}

/**
 * @link http://redis.io/commands/hgetall
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashGetAll extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HGETALL';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        $result = array();

        for ($i = 0; $i < count($data); ++$i) {
            $result[$data[$i]] = $data[++$i];
        }

        return $result;
    }
}

/**
 * @link http://redis.io/commands/info
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerInfoV26x extends ServerInfo
{
    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if ($data === '') {
            return array();
        }

        $info = array();

        $current = null;
        $infoLines = preg_split('/\r?\n/', $data);

        if (isset($infoLines[0]) && $infoLines[0][0] !== '#') {
            return parent::parseResponse($data);
        }

        foreach ($infoLines as $row) {
            if ($row === '') {
                continue;
            }

            if (preg_match('/^# (\w+)$/', $row, $matches)) {
                $info[$matches[1]] = array();
                $current = &$info[$matches[1]];
                continue;
            }

            list($k, $v) = $this->parseRow($row);
            $current[$k] = $v;
        }

        return $info;
    }
}

/**
 * @link http://redis.io/commands/script
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerScript extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SCRIPT';
    }
}

/**
 * @link http://redis.io/topics/sentinel
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerSentinel extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SENTINEL';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        switch (strtolower($this->getArgument(0))) {
            case 'masters':
            case 'slaves':
                return self::processMastersOrSlaves($data);

            default:
                return $data;
        }
    }

    /**
     * Returns a processed response to SENTINEL MASTERS or SENTINEL SLAVES.
     *
     * @param array $servers List of Redis servers.
     *
     * @return array
     */
    protected static function processMastersOrSlaves(array $servers)
    {
        foreach ($servers as $idx => $node) {
            $processed = array();
            $count = count($node);

            for ($i = 0; $i < $count; ++$i) {
                $processed[$node[$i]] = $node[++$i];
            }

            $servers[$idx] = $processed;
        }

        return $servers;
    }
}

/**
 * @link http://redis.io/commands/save
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerSave extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SAVE';
    }
}

/**
 * @link http://redis.io/commands/object
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerObject extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'OBJECT';
    }
}

/**
 * @link http://redis.io/commands/lastsave
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerLastSave extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LASTSAVE';
    }
}

/**
 * @link http://redis.io/commands/monitor
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerMonitor extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'MONITOR';
    }
}

/**
 * @link http://redis.io/commands/client-list
 * @link http://redis.io/commands/client-kill
 * @link http://redis.io/commands/client-getname
 * @link http://redis.io/commands/client-setname
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerClient extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'CLIENT';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        $args = array_change_key_case($this->getArguments(), CASE_UPPER);

        switch (strtoupper($args[0])) {
            case 'LIST':
                return $this->parseClientList($data);
            case 'KILL':
            case 'GETNAME':
            case 'SETNAME':
            default:
                return $data;
        }
    }

    /**
     * Parses the response to CLIENT LIST and returns a structured list.
     *
     * @param string $data Response buffer.
     *
     * @return array
     */
    protected function parseClientList($data)
    {
        $clients = array();

        foreach (explode("\n", $data, -1) as $clientData) {
            $client = array();

            foreach (explode(' ', $clientData) as $kv) {
                @list($k, $v) = explode('=', $kv);
                $client[$k] = $v;
            }

            $clients[] = $client;
        }

        return $clients;
    }
}

/**
 * @link http://redis.io/commands/bgsave
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerBackgroundSave extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BGSAVE';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data === 'Background saving started' ? true : $data;
    }
}

/**
 * @link http://redis.io/commands/ltrim
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListTrim extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LTRIM';
    }
}

/**
 * Defines a command whose keys can be prefixed.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface PrefixableCommandInterface extends CommandInterface
{
    /**
     * Prefixes all the keys found in the arguments of the command.
     *
     * @param string $prefix String used to prefix the keys.
     */
    public function prefixKeys($prefix);
}

/**
 * @link http://redis.io/commands/publish
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PubSubPublish extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PUBLISH';
    }
}

/**
 * @link http://redis.io/commands/lset
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListSet extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LSET';
    }
}

/**
 * @link http://redis.io/commands/lrem
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListRemove extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LREM';
    }
}

/**
 * @link http://redis.io/commands/rpushx
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListPushTailX extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'RPUSHX';
    }
}

/**
 * @link http://redis.io/commands/lrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ListRange extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'LRANGE';
    }
}

/**
 * @link http://redis.io/commands/pubsub
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PubSubPubsub extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PUBSUB';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        switch (strtolower($this->getArgument(0))) {
            case 'numsub':
                return self::processNumsub($data);

            default:
                return $data;
        }
    }

    /**
     * Returns the processed response to PUBSUB NUMSUB.
     *
     * @param array $channels List of channels
     *
     * @return array
     */
    protected static function processNumsub(array $channels)
    {
        $processed = array();
        $count = count($channels);

        for ($i = 0; $i < $count; ++$i) {
            $processed[$channels[$i]] = $channels[++$i];
        }

        return $processed;
    }
}

/**
 * @link http://redis.io/commands/hkeys
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashKeys extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HKEYS';
    }
}

/**
 * Base class used to implement an higher level abstraction for commands based
 * on Lua scripting with EVAL and EVALSHA.
 *
 * @link http://redis.io/commands/eval
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class ScriptCommand extends ServerEvalSHA
{
    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    abstract public function getScript();

    /**
     * Specifies the number of arguments that should be considered as keys.
     *
     * The default behaviour for the base class is to return 0 to indicate that
     * all the elements of the arguments array should be considered as keys, but
     * subclasses can enforce a static number of keys.
     *
     * @return int
     */
    protected function getKeysCount()
    {
        return 0;
    }

    /**
     * Returns the elements from the arguments that are identified as keys.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_slice($this->getArguments(), 2, $this->getKeysCount());
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        if (($numkeys = $this->getKeysCount()) && $numkeys < 0) {
            $numkeys = count($arguments) + $numkeys;
        }

        return array_merge(array(sha1($this->getScript()), (int) $numkeys), $arguments);
    }

    /**
     * @return array
     */
    public function getEvalArguments()
    {
        $arguments = $this->getArguments();
        $arguments[0] = $this->getScript();

        return $arguments;
    }
}

/**
 * @link http://redis.io/commands/bgrewriteaof
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerBackgroundRewriteAOF extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BGREWRITEAOF';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data == 'Background append only file rewriting started';
    }
}

/**
 * Class for generic "anonymous" Redis commands.
 *
 * This command class does not filter input arguments or parse responses, but
 * can be used to leverage the standard Predis API to execute any command simply
 * by providing the needed arguments following the command signature as defined
 * by Redis in its documentation.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RawCommand implements CommandInterface
{
    private $slot;
    private $commandID;
    private $arguments;

    /**
     * @param array $arguments Command ID and its arguments.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $arguments)
    {
        if (!$arguments) {
            throw new \InvalidArgumentException(
                'The arguments array must contain at least the command ID.'
            );
        }

        $this->commandID = strtoupper(array_shift($arguments));
        $this->arguments = $arguments;
    }

    /**
     * Creates a new raw command using a variadic method.
     *
     * @param string $commandID Redis command ID.
     * @param string ...        Arguments list for the command.
     *
     * @return CommandInterface
     */
    public static function create($commandID /* [ $arg, ... */)
    {
        $arguments = func_get_args();
        $command = new self($arguments);

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->commandID;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        unset($this->slot);
    }

    /**
     * {@inheritdoc}
     */
    public function setRawArguments(array $arguments)
    {
        $this->setArguments($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument($index)
    {
        if (isset($this->arguments[$index])) {
            return $this->arguments[$index];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setSlot($slot)
    {
        $this->slot = $slot;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlot()
    {
        if (isset($this->slot)) {
            return $this->slot;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data;
    }
}

/**
 * @link http://redis.io/commands/punsubscribe
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PubSubUnsubscribeByPattern extends PubSubUnsubscribe
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PUNSUBSCRIBE';
    }
}

/**
 * @link http://redis.io/commands/psubscribe
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PubSubSubscribeByPattern extends PubSubSubscribe
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PSUBSCRIBE';
    }
}

/**
 * @link http://redis.io/commands/hincrbyfloat
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashIncrementByFloat extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HINCRBYFLOAT';
    }
}

/**
 * @link http://redis.io/commands/hvals
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashValues extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HVALS';
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Connection;

use Predis\Command\CommandInterface;
use Predis\CommunicationException;
use Predis\Protocol\ProtocolException;
use Predis\Protocol\ProtocolProcessorInterface;
use Predis\Protocol\Text\ProtocolProcessor as TextProtocolProcessor;
use Predis\Command\RawCommand;
use Predis\NotSupportedException;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\Status as StatusResponse;

/**
 * Defines a connection object used to communicate with one or multiple
 * Redis servers.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ConnectionInterface
{
    /**
     * Opens the connection to Redis.
     */
    public function connect();

    /**
     * Closes the connection to Redis.
     */
    public function disconnect();

    /**
     * Checks if the connection to Redis is considered open.
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Writes the request for the given command over the connection.
     *
     * @param CommandInterface $command Command instance.
     */
    public function writeRequest(CommandInterface $command);

    /**
     * Reads the response to the given command from the connection.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function readResponse(CommandInterface $command);

    /**
     * Writes a request for the given command over the connection and reads back
     * the response returned by Redis.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function executeCommand(CommandInterface $command);
}

/**
 * Defines a connection used to communicate with a single Redis node.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface NodeConnectionInterface extends ConnectionInterface
{
    /**
     * Returns a string representation of the connection.
     *
     * @return string
     */
    public function __toString();

    /**
     * Returns the underlying resource used to communicate with Redis.
     *
     * @return mixed
     */
    public function getResource();

    /**
     * Returns the parameters used to initialize the connection.
     *
     * @return ParametersInterface
     */
    public function getParameters();

    /**
     * Pushes the given command into a queue of commands executed when
     * establishing the actual connection to Redis.
     *
     * @param CommandInterface $command Instance of a Redis command.
     */
    public function addConnectCommand(CommandInterface $command);

    /**
     * Reads a response from the server.
     *
     * @return mixed
     */
    public function read();
}

/**
 * Defines a virtual connection composed of multiple connection instances to
 * single Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface AggregateConnectionInterface extends ConnectionInterface
{
    /**
     * Adds a connection instance to the aggregate connection.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     */
    public function add(NodeConnectionInterface $connection);

    /**
     * Removes the specified connection instance from the aggregate connection.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     *
     * @return bool Returns true if the connection was in the pool.
     */
    public function remove(NodeConnectionInterface $connection);

    /**
     * Returns the connection instance in charge for the given command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return NodeConnectionInterface
     */
    public function getConnection(CommandInterface $command);

    /**
     * Returns a connection instance from the aggregate connection by its alias.
     *
     * @param string $connectionID Connection alias.
     *
     * @return NodeConnectionInterface|null
     */
    public function getConnectionById($connectionID);
}

/**
 * Base class with the common logic used by connection classes to communicate
 * with Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class AbstractConnection implements NodeConnectionInterface
{
    private $resource;
    private $cachedId;

    protected $parameters;
    protected $initCommands = array();

    /**
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     */
    public function __construct(ParametersInterface $parameters)
    {
        $this->parameters = $this->assertParameters($parameters);
    }

    /**
     * Disconnects from the server and destroys the underlying resource when
     * PHP's garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Checks some of the parameters used to initialize the connection.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @throws \InvalidArgumentException
     *
     * @return ParametersInterface
     */
    protected function assertParameters(ParametersInterface $parameters)
    {
        switch ($parameters->scheme) {
            case 'tcp':
            case 'redis':
            case 'unix':
                break;

            default:
                throw new \InvalidArgumentException("Invalid scheme: '$parameters->scheme'.");
        }

        return $parameters;
    }

    /**
     * Creates the underlying resource used to communicate with Redis.
     *
     * @return mixed
     */
    abstract protected function createResource();

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return isset($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $this->resource = $this->createResource();

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        unset($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function addConnectCommand(CommandInterface $command)
    {
        $this->initCommands[] = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $this->writeRequest($command);

        return $this->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->read();
    }

    /**
     * Helper method that returns an exception message augmented with useful
     * details from the connection parameters.
     *
     * @param string $message Error message.
     *
     * @return string
     */
    private function createExceptionMessage($message)
    {
        $parameters = $this->parameters;

        if ($parameters->scheme === 'unix') {
            return "$message [$parameters->scheme:$parameters->path]";
        }

        if (filter_var($parameters->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return "$message [$parameters->scheme://[$parameters->host]:$parameters->port]";
        }

        return "$message [$parameters->scheme://$parameters->host:$parameters->port]";
    }

    /**
     * Helper method to handle connection errors.
     *
     * @param string $message Error message.
     * @param int    $code    Error code.
     */
    protected function onConnectionError($message, $code = null)
    {
        CommunicationException::handle(
            new ConnectionException($this, static::createExceptionMessage($message), $code)
        );
    }

    /**
     * Helper method to handle protocol errors.
     *
     * @param string $message Error message.
     */
    protected function onProtocolError($message)
    {
        CommunicationException::handle(
            new ProtocolException($this, static::createExceptionMessage($message))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        if (isset($this->resource)) {
            return $this->resource;
        }

        $this->connect();

        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Gets an identifier for the connection.
     *
     * @return string
     */
    protected function getIdentifier()
    {
        if ($this->parameters->scheme === 'unix') {
            return $this->parameters->path;
        }

        return "{$this->parameters->host}:{$this->parameters->port}";
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (!isset($this->cachedId)) {
            $this->cachedId = $this->getIdentifier();
        }

        return $this->cachedId;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array('parameters', 'initCommands');
    }
}

/**
 * Standard connection to Redis servers implemented on top of PHP's streams.
 * The connection parameters supported by this class are:.
 *
 *  - scheme: it can be either 'redis', 'tcp' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection.
 *  - read_write_timeout: timeout of read / write operations.
 *  - async_connect: performs the connection asynchronously.
 *  - tcp_nodelay: enables or disables Nagle's algorithm for coalescing.
 *  - persistent: the connection is left intact after a GC collection.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StreamConnection extends AbstractConnection
{
    /**
     * Disconnects from the server and destroys the underlying resource when the
     * garbage collector kicks in only if the connection has not been marked as
     * persistent.
     */
    public function __destruct()
    {
        if (isset($this->parameters->persistent) && $this->parameters->persistent) {
            return;
        }

        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    protected function createResource()
    {
        switch ($this->parameters->scheme) {
            case 'tcp':
            case 'redis':
                return $this->tcpStreamInitializer($this->parameters);

            case 'unix':
                return $this->unixStreamInitializer($this->parameters);

            default:
                throw new \InvalidArgumentException("Invalid scheme: '{$this->parameters->scheme}'.");
        }
    }

    /**
     * Initializes a TCP stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     */
    protected function tcpStreamInitializer(ParametersInterface $parameters)
    {
        if (!filter_var($parameters->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $uri = "tcp://$parameters->host:$parameters->port";
        } else {
            $uri = "tcp://[$parameters->host]:$parameters->port";
        }

        $flags = STREAM_CLIENT_CONNECT;

        if (isset($parameters->async_connect) && (bool) $parameters->async_connect) {
            $flags |= STREAM_CLIENT_ASYNC_CONNECT;
        }

        if (isset($parameters->persistent) && (bool) $parameters->persistent) {
            $flags |= STREAM_CLIENT_PERSISTENT;
            $uri .= strpos($path = $parameters->path, '/') === 0 ? $path : "/$path";
        }

        $resource = @stream_socket_client($uri, $errno, $errstr, (float) $parameters->timeout, $flags);

        if (!$resource) {
            $this->onConnectionError(trim($errstr), $errno);
        }

        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;
            $timeoutSeconds = floor($rwtimeout);
            $timeoutUSeconds = ($rwtimeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($resource, $timeoutSeconds, $timeoutUSeconds);
        }

        if (isset($parameters->tcp_nodelay) && function_exists('socket_import_stream')) {
            $socket = socket_import_stream($resource);
            socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int) $parameters->tcp_nodelay);
        }

        return $resource;
    }

    /**
     * Initializes a UNIX stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     */
    protected function unixStreamInitializer(ParametersInterface $parameters)
    {
        if (!isset($parameters->path)) {
            throw new InvalidArgumentException('Missing UNIX domain socket path.');
        }

        $uri = "unix://{$parameters->path}";
        $flags = STREAM_CLIENT_CONNECT;

        if ((bool) $parameters->persistent) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }

        $resource = @stream_socket_client($uri, $errno, $errstr, (float) $parameters->timeout, $flags);

        if (!$resource) {
            $this->onConnectionError(trim($errstr), $errno);
        }

        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;
            $timeoutSeconds = floor($rwtimeout);
            $timeoutUSeconds = ($rwtimeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($resource, $timeoutSeconds, $timeoutUSeconds);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (parent::connect() && $this->initCommands) {
            foreach ($this->initCommands as $command) {
                $this->executeCommand($command);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            fclose($this->getResource());
            parent::disconnect();
        }
    }

    /**
     * Performs a write operation over the stream of the buffer containing a
     * command serialized with the Redis wire protocol.
     *
     * @param string $buffer Representation of a command in the Redis wire protocol.
     */
    protected function write($buffer)
    {
        $socket = $this->getResource();

        while (($length = strlen($buffer)) > 0) {
            $written = @fwrite($socket, $buffer);

            if ($length === $written) {
                return;
            }

            if ($written === false || $written === 0) {
                $this->onConnectionError('Error while writing bytes to the server.');
            }

            $buffer = substr($buffer, $written);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $socket = $this->getResource();
        $chunk = fgets($socket);

        if ($chunk === false || $chunk === '') {
            $this->onConnectionError('Error while reading line from the server.');
        }

        $prefix = $chunk[0];
        $payload = substr($chunk, 1, -2);

        switch ($prefix) {
            case '+':
                return StatusResponse::get($payload);

            case '$':
                $size = (int) $payload;

                if ($size === -1) {
                    return;
                }

                $bulkData = '';
                $bytesLeft = ($size += 2);

                do {
                    $chunk = fread($socket, min($bytesLeft, 4096));

                    if ($chunk === false || $chunk === '') {
                        $this->onConnectionError('Error while reading bytes from the server.');
                    }

                    $bulkData .= $chunk;
                    $bytesLeft = $size - strlen($bulkData);
                } while ($bytesLeft > 0);

                return substr($bulkData, 0, -2);

            case '*':
                $count = (int) $payload;

                if ($count === -1) {
                    return;
                }

                $multibulk = array();

                for ($i = 0; $i < $count; ++$i) {
                    $multibulk[$i] = $this->read();
                }

                return $multibulk;

            case ':':
                return (int) $payload;

            case '-':
                return new ErrorResponse($payload);

            default:
                $this->onProtocolError("Unknown response prefix: '$prefix'.");

                return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $commandID = $command->getId();
        $arguments = $command->getArguments();

        $cmdlen = strlen($commandID);
        $reqlen = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandID}\r\n";

        for ($i = 0, $reqlen--; $i < $reqlen; ++$i) {
            $argument = $arguments[$i];
            $arglen = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        $this->write($buffer);
    }
}

/**
 * Interface defining a container for connection parameters.
 *
 * The actual list of connection parameters depends on the features supported by
 * each connection backend class (please refer to their specific documentation),
 * but the most common parameters used through the library are:
 *
 * @property-read string scheme             Connection scheme, such as 'tcp' or 'unix'.
 * @property-read string host               IP address or hostname of Redis.
 * @property-read int    port               TCP port on which Redis is listening to.
 * @property-read string path               Path of a UNIX domain socket file.
 * @property-read string alias              Alias for the connection.
 * @property-read float  timeout            Timeout for the connect() operation.
 * @property-read float  read_write_timeout Timeout for read() and write() operations.
 * @property-read bool   async_connect      Performs the connect() operation asynchronously.
 * @property-read bool   tcp_nodelay        Toggles the Nagle's algorithm for coalescing.
 * @property-read bool   persistent         Leaves the connection open after a GC collection.
 * @property-read string password           Password to access Redis (see the AUTH command).
 * @property-read string database           Database index (see the SELECT command).
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ParametersInterface
{
    /**
     * Checks if the specified parameters is set.
     *
     * @param string $parameter Name of the parameter.
     *
     * @return bool
     */
    public function __isset($parameter);

    /**
     * Returns the value of the specified parameter.
     *
     * @param string $parameter Name of the parameter.
     *
     * @return mixed|null
     */
    public function __get($parameter);

    /**
     * Returns an array representation of the connection parameters.
     *
     * @return array
     */
    public function toArray();
}

/**
 * Interface for classes providing a factory of connections to Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface FactoryInterface
{
    /**
     * Defines or overrides the connection class identified by a scheme prefix.
     *
     * @param string $scheme      Target connection scheme.
     * @param mixed  $initializer Fully-qualified name of a class or a callable for lazy initialization.
     */
    public function define($scheme, $initializer);

    /**
     * Undefines the connection identified by a scheme prefix.
     *
     * @param string $scheme Target connection scheme.
     */
    public function undefine($scheme);

    /**
     * Creates a new connection object.
     *
     * @param mixed $parameters Initialization parameters for the connection.
     *
     * @return NodeConnectionInterface
     */
    public function create($parameters);

    /**
     * Aggregates single connections into an aggregate connection instance.
     *
     * @param AggregateConnectionInterface $aggregate  Aggregate connection instance.
     * @param array                        $parameters List of parameters for each connection.
     */
    public function aggregate(AggregateConnectionInterface $aggregate, array $parameters);
}

/**
 * Defines a connection to communicate with a single Redis server that leverages
 * an external protocol processor to handle pluggable protocol handlers.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface CompositeConnectionInterface extends NodeConnectionInterface
{
    /**
     * Returns the protocol processor used by the connection.
     */
    public function getProtocol();

    /**
     * Writes the buffer containing over the connection.
     *
     * @param string $buffer String buffer to be sent over the connection.
     */
    public function writeBuffer($buffer);

    /**
     * Reads the given number of bytes from the connection.
     *
     * @param int $length Number of bytes to read from the connection.
     *
     * @return string
     */
    public function readBuffer($length);

    /**
     * Reads a line from the connection.
     *
     * @param string
     */
    public function readLine();
}

/**
 * This class provides the implementation of a Predis connection that uses PHP's
 * streams for network communication and wraps the phpiredis C extension (PHP
 * bindings for hiredis) to parse and serialize the Redis protocol.
 *
 * This class is intended to provide an optional low-overhead alternative for
 * processing responses from Redis compared to the standard pure-PHP classes.
 * Differences in speed when dealing with short inline responses are practically
 * nonexistent, the actual speed boost is for big multibulk responses when this
 * protocol processor can parse and return responses very fast.
 *
 * For instructions on how to build and install the phpiredis extension, please
 * consult the repository of the project.
 *
 * The connection parameters supported by this class are:
 *
 *  - scheme: it can be either 'redis', 'tcp' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection.
 *  - read_write_timeout: timeout of read / write operations.
 *  - async_connect: performs the connection asynchronously.
 *  - tcp_nodelay: enables or disables Nagle's algorithm for coalescing.
 *  - persistent: the connection is left intact after a GC collection.
 *
 * @link https://github.com/nrk/phpiredis
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PhpiredisStreamConnection extends StreamConnection
{
    private $reader;

    /**
     * {@inheritdoc}
     */
    public function __construct(ParametersInterface $parameters)
    {
        $this->assertExtensions();

        parent::__construct($parameters);

        $this->reader = $this->createReader();
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        phpiredis_reader_destroy($this->reader);

        parent::__destruct();
    }

    /**
     * Checks if the phpiredis extension is loaded in PHP.
     */
    private function assertExtensions()
    {
        if (!extension_loaded('phpiredis')) {
            throw new NotSupportedException(
                'The "phpiredis" extension is required by this connection backend.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tcpStreamInitializer(ParametersInterface $parameters)
    {
        $uri = "tcp://[{$parameters->host}]:{$parameters->port}";
        $flags = STREAM_CLIENT_CONNECT;
        $socket = null;

        if (isset($parameters->async_connect) && (bool) $parameters->async_connect) {
            $flags |= STREAM_CLIENT_ASYNC_CONNECT;
        }

        if (isset($parameters->persistent) && (bool) $parameters->persistent) {
            $flags |= STREAM_CLIENT_PERSISTENT;
            $uri .= strpos($path = $parameters->path, '/') === 0 ? $path : "/$path";
        }

        $resource = @stream_socket_client($uri, $errno, $errstr, (float) $parameters->timeout, $flags);

        if (!$resource) {
            $this->onConnectionError(trim($errstr), $errno);
        }

        if (isset($parameters->read_write_timeout) && function_exists('socket_import_stream')) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;

            $timeout = array(
                'sec' => $timeoutSeconds = floor($rwtimeout),
                'usec' => ($rwtimeout - $timeoutSeconds) * 1000000,
            );

            $socket = $socket ?: socket_import_stream($resource);
            @socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout);
            @socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
        }

        if (isset($parameters->tcp_nodelay) && function_exists('socket_import_stream')) {
            $socket = $socket ?: socket_import_stream($resource);
            socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int) $parameters->tcp_nodelay);
        }

        return $resource;
    }

    /**
     * Creates a new instance of the protocol reader resource.
     *
     * @return resource
     */
    private function createReader()
    {
        $reader = phpiredis_reader_create();

        phpiredis_reader_set_status_handler($reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($reader, $this->getErrorHandler());

        return $reader;
    }

    /**
     * Returns the underlying protocol reader resource.
     *
     * @return resource
     */
    protected function getReader()
    {
        return $this->reader;
    }

    /**
     * Returns the handler used by the protocol reader for inline responses.
     *
     * @return \Closure
     */
    protected function getStatusHandler()
    {
        return function ($payload) {
            return StatusResponse::get($payload);
        };
    }

    /**
     * Returns the handler used by the protocol reader for error responses.
     *
     * @return \Closure
     */
    protected function getErrorHandler()
    {
        return function ($errorMessage) {
            return new ErrorResponse($errorMessage);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $socket = $this->getResource();
        $reader = $this->reader;

        while (PHPIREDIS_READER_STATE_INCOMPLETE === $state = phpiredis_reader_get_state($reader)) {
            $buffer = stream_socket_recvfrom($socket, 4096);

            if ($buffer === false || $buffer === '') {
                $this->onConnectionError('Error while reading bytes from the server.');
            }

            phpiredis_reader_feed($reader, $buffer);
        }

        if ($state === PHPIREDIS_READER_STATE_COMPLETE) {
            return phpiredis_reader_get_reply($reader);
        } else {
            $this->onProtocolError(phpiredis_reader_get_error($reader));

            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $arguments = $command->getArguments();
        array_unshift($arguments, $command->getId());

        $this->write(phpiredis_format_command($arguments));
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup()
    {
        $this->assertExtensions();
        $this->reader = $this->createReader();
    }
}

/**
 * This class implements a Predis connection that actually talks with Webdis
 * instead of connecting directly to Redis. It relies on the cURL extension to
 * communicate with the web server and the phpiredis extension to parse the
 * protocol for responses returned in the http response bodies.
 *
 * Some features are not yet available or they simply cannot be implemented:
 *   - Pipelining commands.
 *   - Publish / Subscribe.
 *   - MULTI / EXEC transactions (not yet supported by Webdis).
 *
 * The connection parameters supported by this class are:
 *
 *  - scheme: must be 'http'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - timeout: timeout to perform the connection.
 *  - user: username for authentication.
 *  - pass: password for authentication.
 *
 * @link http://webd.is
 * @link http://github.com/nicolasff/webdis
 * @link http://github.com/seppo0010/phpiredis
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class WebdisConnection implements NodeConnectionInterface
{
    private $parameters;
    private $resource;
    private $reader;

    /**
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(ParametersInterface $parameters)
    {
        $this->assertExtensions();

        if ($parameters->scheme !== 'http') {
            throw new \InvalidArgumentException("Invalid scheme: '{$parameters->scheme}'.");
        }

        $this->parameters = $parameters;

        $this->resource = $this->createCurl();
        $this->reader = $this->createReader();
    }

    /**
     * Frees the underlying cURL and protocol reader resources when the garbage
     * collector kicks in.
     */
    public function __destruct()
    {
        curl_close($this->resource);
        phpiredis_reader_destroy($this->reader);
    }

    /**
     * Helper method used to throw on unsupported methods.
     *
     * @param string $method Name of the unsupported method.
     *
     * @throws NotSupportedException
     */
    private function throwNotSupportedException($method)
    {
        $class = __CLASS__;
        throw new NotSupportedException("The method $class::$method() is not supported.");
    }

    /**
     * Checks if the cURL and phpiredis extensions are loaded in PHP.
     */
    private function assertExtensions()
    {
        if (!extension_loaded('curl')) {
            throw new NotSupportedException(
                'The "curl" extension is required by this connection backend.'
            );
        }

        if (!extension_loaded('phpiredis')) {
            throw new NotSupportedException(
                'The "phpiredis" extension is required by this connection backend.'
            );
        }
    }

    /**
     * Initializes cURL.
     *
     * @return resource
     */
    private function createCurl()
    {
        $parameters = $this->getParameters();

        if (filter_var($host = $parameters->host, FILTER_VALIDATE_IP)) {
            $host = "[$host]";
        }

        $options = array(
            CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT_MS => $parameters->timeout * 1000,
            CURLOPT_URL => "$parameters->scheme://$host:$parameters->port",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_WRITEFUNCTION => array($this, 'feedReader'),
        );

        if (isset($parameters->user, $parameters->pass)) {
            $options[CURLOPT_USERPWD] = "{$parameters->user}:{$parameters->pass}";
        }

        curl_setopt_array($resource = curl_init(), $options);

        return $resource;
    }

    /**
     * Initializes the phpiredis protocol reader.
     *
     * @return resource
     */
    private function createReader()
    {
        $reader = phpiredis_reader_create();

        phpiredis_reader_set_status_handler($reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($reader, $this->getErrorHandler());

        return $reader;
    }

    /**
     * Returns the handler used by the protocol reader for inline responses.
     *
     * @return \Closure
     */
    protected function getStatusHandler()
    {
        return function ($payload) {
            return StatusResponse::get($payload);
        };
    }

    /**
     * Returns the handler used by the protocol reader for error responses.
     *
     * @return \Closure
     */
    protected function getErrorHandler()
    {
        return function ($payload) {
            return new ErrorResponse($payload);
        };
    }

    /**
     * Feeds the phpredis reader resource with the data read from the network.
     *
     * @param resource $resource Reader resource.
     * @param string   $buffer   Buffer of data read from a connection.
     *
     * @return int
     */
    protected function feedReader($resource, $buffer)
    {
        phpiredis_reader_feed($this->reader, $buffer);

        return strlen($buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        // NOOP
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        // NOOP
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return true;
    }

    /**
     * Checks if the specified command is supported by this connection class.
     *
     * @param CommandInterface $command Command instance.
     *
     * @throws NotSupportedException
     *
     * @return string
     */
    protected function getCommandId(CommandInterface $command)
    {
        switch ($commandID = $command->getId()) {
            case 'AUTH':
            case 'SELECT':
            case 'MULTI':
            case 'EXEC':
            case 'WATCH':
            case 'UNWATCH':
            case 'DISCARD':
            case 'MONITOR':
                throw new NotSupportedException("Command '$commandID' is not allowed by Webdis.");

            default:
                return $commandID;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $resource = $this->resource;
        $commandId = $this->getCommandId($command);

        if ($arguments = $command->getArguments()) {
            $arguments = implode('/', array_map('urlencode', $arguments));
            $serializedCommand = "$commandId/$arguments.raw";
        } else {
            $serializedCommand = "$commandId.raw";
        }

        curl_setopt($resource, CURLOPT_POSTFIELDS, $serializedCommand);

        if (curl_exec($resource) === false) {
            $error = curl_error($resource);
            $errno = curl_errno($resource);

            throw new ConnectionException($this, trim($error), $errno);
        }

        if (phpiredis_reader_get_state($this->reader) !== PHPIREDIS_READER_STATE_COMPLETE) {
            throw new ProtocolException($this, phpiredis_reader_get_error($this->reader));
        }

        return phpiredis_reader_get_reply($this->reader);
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function addConnectCommand(CommandInterface $command)
    {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return "{$this->parameters->host}:{$this->parameters->port}";
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array('parameters');
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup()
    {
        $this->assertExtensions();

        $this->resource = $this->createCurl();
        $this->reader = $this->createReader();
    }
}

/**
 * This class provides the implementation of a Predis connection that uses the
 * PHP socket extension for network communication and wraps the phpiredis C
 * extension (PHP bindings for hiredis) to parse the Redis protocol.
 *
 * This class is intended to provide an optional low-overhead alternative for
 * processing responses from Redis compared to the standard pure-PHP classes.
 * Differences in speed when dealing with short inline responses are practically
 * nonexistent, the actual speed boost is for big multibulk responses when this
 * protocol processor can parse and return responses very fast.
 *
 * For instructions on how to build and install the phpiredis extension, please
 * consult the repository of the project.
 *
 * The connection parameters supported by this class are:
 *
 *  - scheme: it can be either 'redis', 'tcp' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection.
 *  - read_write_timeout: timeout of read / write operations.
 *
 * @link http://github.com/nrk/phpiredis
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PhpiredisSocketConnection extends AbstractConnection
{
    private $reader;

    /**
     * {@inheritdoc}
     */
    public function __construct(ParametersInterface $parameters)
    {
        $this->assertExtensions();

        parent::__construct($parameters);

        $this->reader = $this->createReader();
    }

    /**
     * Disconnects from the server and destroys the underlying resource and the
     * protocol reader resource when PHP's garbage collector kicks in.
     */
    public function __destruct()
    {
        phpiredis_reader_destroy($this->reader);

        parent::__destruct();
    }

    /**
     * Checks if the socket and phpiredis extensions are loaded in PHP.
     */
    protected function assertExtensions()
    {
        if (!extension_loaded('sockets')) {
            throw new NotSupportedException(
                'The "sockets" extension is required by this connection backend.'
            );
        }

        if (!extension_loaded('phpiredis')) {
            throw new NotSupportedException(
                'The "phpiredis" extension is required by this connection backend.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function assertParameters(ParametersInterface $parameters)
    {
        parent::assertParameters($parameters);

        if (isset($parameters->persistent)) {
            throw new NotSupportedException(
                'Persistent connections are not supported by this connection backend.'
            );
        }

        return $parameters;
    }

    /**
     * Creates a new instance of the protocol reader resource.
     *
     * @return resource
     */
    private function createReader()
    {
        $reader = phpiredis_reader_create();

        phpiredis_reader_set_status_handler($reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($reader, $this->getErrorHandler());

        return $reader;
    }

    /**
     * Returns the underlying protocol reader resource.
     *
     * @return resource
     */
    protected function getReader()
    {
        return $this->reader;
    }

    /**
     * Returns the handler used by the protocol reader for inline responses.
     *
     * @return \Closure
     */
    private function getStatusHandler()
    {
        return function ($payload) {
            return StatusResponse::get($payload);
        };
    }

    /**
     * Returns the handler used by the protocol reader for error responses.
     *
     * @return \Closure
     */
    protected function getErrorHandler()
    {
        return function ($payload) {
            return new ErrorResponse($payload);
        };
    }

    /**
     * Helper method used to throw exceptions on socket errors.
     */
    private function emitSocketError()
    {
        $errno = socket_last_error();
        $errstr = socket_strerror($errno);

        $this->disconnect();

        $this->onConnectionError(trim($errstr), $errno);
    }

    /**
     * Gets the address of an host from connection parameters.
     *
     * @param ParametersInterface $parameters Parameters used to initialize the connection.
     *
     * @return string
     */
    protected static function getAddress(ParametersInterface $parameters)
    {
        if (filter_var($host = $parameters->host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        if ($host === $address = gethostbyname($host)) {
            return false;
        }

        return $address;
    }

    /**
     * {@inheritdoc}
     */
    protected function createResource()
    {
        $parameters = $this->parameters;

        if ($parameters->scheme === 'unix') {
            $address = $parameters->path;
            $domain = AF_UNIX;
            $protocol = 0;
        } else {
            if (false === $address = self::getAddress($parameters)) {
                $this->onConnectionError("Cannot resolve the address of '$parameters->host'.");
            }

            $domain = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? AF_INET6 : AF_INET;
            $protocol = SOL_TCP;
        }

        $socket = @socket_create($domain, SOCK_STREAM, $protocol);

        if (!is_resource($socket)) {
            $this->emitSocketError();
        }

        $this->setSocketOptions($socket, $parameters);
        $this->connectWithTimeout($socket, $address, $parameters);

        return $socket;
    }

    /**
     * Sets options on the socket resource from the connection parameters.
     *
     * @param resource            $socket     Socket resource.
     * @param ParametersInterface $parameters Parameters used to initialize the connection.
     */
    private function setSocketOptions($socket, ParametersInterface $parameters)
    {
        if ($parameters->scheme !== 'unix') {
            if (!socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1)) {
                $this->emitSocketError();
            }

            if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
                $this->emitSocketError();
            }
        }

        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $timeoutSec = floor($rwtimeout);
            $timeoutUsec = ($rwtimeout - $timeoutSec) * 1000000;

            $timeout = array(
                'sec' => $timeoutSec,
                'usec' => $timeoutUsec,
            );

            if (!socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout)) {
                $this->emitSocketError();
            }

            if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout)) {
                $this->emitSocketError();
            }
        }
    }

    /**
     * Opens the actual connection to the server with a timeout.
     *
     * @param resource            $socket     Socket resource.
     * @param string              $address    IP address (DNS-resolved from hostname)
     * @param ParametersInterface $parameters Parameters used to initialize the connection.
     *
     * @return string
     */
    private function connectWithTimeout($socket, $address, ParametersInterface $parameters)
    {
        socket_set_nonblock($socket);

        if (@socket_connect($socket, $address, (int) $parameters->port) === false) {
            $error = socket_last_error();

            if ($error != SOCKET_EINPROGRESS && $error != SOCKET_EALREADY) {
                $this->emitSocketError();
            }
        }

        socket_set_block($socket);

        $null = null;
        $selectable = array($socket);

        $timeout = (float) $parameters->timeout;
        $timeoutSecs = floor($timeout);
        $timeoutUSecs = ($timeout - $timeoutSecs) * 1000000;

        $selected = socket_select($selectable, $selectable, $null, $timeoutSecs, $timeoutUSecs);

        if ($selected === 2) {
            $this->onConnectionError('Connection refused.', SOCKET_ECONNREFUSED);
        }

        if ($selected === 0) {
            $this->onConnectionError('Connection timed out.', SOCKET_ETIMEDOUT);
        }

        if ($selected === false) {
            $this->emitSocketError();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (parent::connect() && $this->initCommands) {
            foreach ($this->initCommands as $command) {
                $this->executeCommand($command);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            socket_close($this->getResource());
            parent::disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function write($buffer)
    {
        $socket = $this->getResource();

        while (($length = strlen($buffer)) > 0) {
            $written = socket_write($socket, $buffer, $length);

            if ($length === $written) {
                return;
            }

            if ($written === false) {
                $this->onConnectionError('Error while writing bytes to the server.');
            }

            $buffer = substr($buffer, $written);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $socket = $this->getResource();
        $reader = $this->reader;

        while (PHPIREDIS_READER_STATE_INCOMPLETE === $state = phpiredis_reader_get_state($reader)) {
            if (@socket_recv($socket, $buffer, 4096, 0) === false || $buffer === '' || $buffer === null) {
                $this->emitSocketError();
            }

            phpiredis_reader_feed($reader, $buffer);
        }

        if ($state === PHPIREDIS_READER_STATE_COMPLETE) {
            return phpiredis_reader_get_reply($reader);
        } else {
            $this->onProtocolError(phpiredis_reader_get_error($reader));

            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $arguments = $command->getArguments();
        array_unshift($arguments, $command->getId());

        $this->write(phpiredis_format_command($arguments));
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup()
    {
        $this->assertExtensions();
        $this->reader = $this->createReader();
    }
}

/**
 * Connection abstraction to Redis servers based on PHP's stream that uses an
 * external protocol processor defining the protocol used for the communication.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class CompositeStreamConnection extends StreamConnection implements CompositeConnectionInterface
{
    protected $protocol;

    /**
     * @param ParametersInterface        $parameters Initialization parameters for the connection.
     * @param ProtocolProcessorInterface $protocol   Protocol processor.
     */
    public function __construct(
        ParametersInterface $parameters,
        ProtocolProcessorInterface $protocol = null
    ) {
        $this->parameters = $this->assertParameters($parameters);
        $this->protocol = $protocol ?: new TextProtocolProcessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function writeBuffer($buffer)
    {
        $this->write($buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function readBuffer($length)
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Length parameter must be greater than 0.');
        }

        $value = '';
        $socket = $this->getResource();

        do {
            $chunk = fread($socket, $length);

            if ($chunk === false || $chunk === '') {
                $this->onConnectionError('Error while reading bytes from the server.');
            }

            $value .= $chunk;
        } while (($length -= strlen($chunk)) > 0);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function readLine()
    {
        $value = '';
        $socket = $this->getResource();

        do {
            $chunk = fgets($socket);

            if ($chunk === false || $chunk === '') {
                $this->onConnectionError('Error while reading line from the server.');
            }

            $value .= $chunk;
        } while (substr($value, -2) !== "\r\n");

        return substr($value, 0, -2);
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->protocol->write($this, $command);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        return $this->protocol->read($this);
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), array('protocol'));
    }
}

/**
 * Exception class that identifies connection-related errors.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionException extends CommunicationException
{
}

/**
 * Container for connection parameters used to initialize connections to Redis.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Parameters implements ParametersInterface
{
    private $parameters;

    private static $defaults = array(
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 5.0,
    );

    /**
     * @param array $parameters Named array of connection parameters.
     */
    public function __construct(array $parameters = array())
    {
        $this->parameters = $this->filter($parameters) + $this->getDefaults();
    }

    /**
     * Returns some default parameters with their values.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return self::$defaults;
    }

    /**
     * Creates a new instance by supplying the initial parameters either in the
     * form of an URI string or a named array.
     *
     * @param array|string $parameters Set of connection parameters.
     *
     * @return Parameters
     */
    public static function create($parameters)
    {
        if (is_string($parameters)) {
            $parameters = static::parse($parameters);
        }

        return new static($parameters ?: array());
    }

    /**
     * Parses an URI string returning an array of connection parameters.
     *
     * When using the "redis" and "rediss" schemes the URI is parsed according
     * to the rules defined by the provisional registration documents approved
     * by IANA. If the URI has a password in its "user-information" part or a
     * database number in the "path" part these values override the values of
     * "password" and "database" if they are present in the "query" part.
     *
     * @link http://www.iana.org/assignments/uri-schemes/prov/redis
     * @link http://www.iana.org/assignments/uri-schemes/prov/redis
     *
     * @param string $uri URI string.
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public static function parse($uri)
    {
        if (stripos($uri, 'unix') === 0) {
            // Hack to support URIs for UNIX sockets with minimal effort.
            $uri = str_ireplace('unix:///', 'unix://localhost/', $uri);
        }

        if (!$parsed = parse_url($uri)) {
            throw new \InvalidArgumentException("Invalid parameters URI: $uri");
        }

        if (
            isset($parsed['host'])
            && false !== strpos($parsed['host'], '[')
            && false !== strpos($parsed['host'], ']')
        ) {
            $parsed['host'] = substr($parsed['host'], 1, -1);
        }

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryarray);
            unset($parsed['query']);

            $parsed = array_merge($parsed, $queryarray);
        }

        if (stripos($uri, 'redis') === 0) {
            if (isset($parsed['pass'])) {
                $parsed['password'] = $parsed['pass'];
                unset($parsed['pass']);
            }

            if (isset($parsed['path']) && preg_match('/^\/(\d+)(\/.*)?/', $parsed['path'], $path)) {
                $parsed['database'] = $path[1];

                if (isset($path[2])) {
                    $parsed['path'] = $path[2];
                } else {
                    unset($parsed['path']);
                }
            }
        }

        return $parsed;
    }

    /**
     * Validates and converts each value of the connection parameters array.
     *
     * @param array $parameters Connection parameters.
     *
     * @return array
     */
    protected function filter(array $parameters)
    {
        return $parameters ?: array();
    }

    /**
     * {@inheritdoc}
     */
    public function __get($parameter)
    {
        if (isset($this->parameters[$parameter])) {
            return $this->parameters[$parameter];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($parameter)
    {
        return isset($this->parameters[$parameter]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array('parameters');
    }
}

/**
 * Standard connection factory for creating connections to Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Factory implements FactoryInterface
{
    protected $schemes = array(
        'tcp' => 'Predis\Connection\StreamConnection',
        'unix' => 'Predis\Connection\StreamConnection',
        'redis' => 'Predis\Connection\StreamConnection',
        'http' => 'Predis\Connection\WebdisConnection',
    );

    /**
     * Checks if the provided argument represents a valid connection class
     * implementing Predis\Connection\NodeConnectionInterface. Optionally,
     * callable objects are used for lazy initialization of connection objects.
     *
     * @param mixed $initializer FQN of a connection class or a callable for lazy initialization.
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    protected function checkInitializer($initializer)
    {
        if (is_callable($initializer)) {
            return $initializer;
        }

        $class = new \ReflectionClass($initializer);

        if (!$class->isSubclassOf('Predis\Connection\NodeConnectionInterface')) {
            throw new \InvalidArgumentException(
                'A connection initializer must be a valid connection class or a callable object.'
            );
        }

        return $initializer;
    }

    /**
     * {@inheritdoc}
     */
    public function define($scheme, $initializer)
    {
        $this->schemes[$scheme] = $this->checkInitializer($initializer);
    }

    /**
     * {@inheritdoc}
     */
    public function undefine($scheme)
    {
        unset($this->schemes[$scheme]);
    }

    /**
     * {@inheritdoc}
     */
    public function create($parameters)
    {
        if (!$parameters instanceof ParametersInterface) {
            $parameters = $this->createParameters($parameters);
        }

        $scheme = $parameters->scheme;

        if (!isset($this->schemes[$scheme])) {
            throw new \InvalidArgumentException("Unknown connection scheme: '$scheme'.");
        }

        $initializer = $this->schemes[$scheme];

        if (is_callable($initializer)) {
            $connection = call_user_func($initializer, $parameters, $this);
        } else {
            $connection = new $initializer($parameters);
            $this->prepareConnection($connection);
        }

        if (!$connection instanceof NodeConnectionInterface) {
            throw new \UnexpectedValueException(
                'Objects returned by connection initializers must implement '.
                "'Predis\Connection\NodeConnectionInterface'."
            );
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function aggregate(AggregateConnectionInterface $connection, array $parameters)
    {
        foreach ($parameters as $node) {
            $connection->add($node instanceof NodeConnectionInterface ? $node : $this->create($node));
        }
    }

    /**
     * Creates a connection parameters instance from the supplied argument.
     *
     * @param mixed $parameters Original connection parameters.
     *
     * @return ParametersInterface
     */
    protected function createParameters($parameters)
    {
        return Parameters::create($parameters);
    }

    /**
     * Prepares a connection instance after its initialization.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     */
    protected function prepareConnection(NodeConnectionInterface $connection)
    {
        $parameters = $connection->getParameters();

        if (isset($parameters->password)) {
            $connection->addConnectCommand(
                new RawCommand(array('AUTH', $parameters->password))
            );
        }

        if (isset($parameters->database)) {
            $connection->addConnectCommand(
                new RawCommand(array('SELECT', $parameters->database))
            );
        }
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Profile;

use Predis\ClientException;
use Predis\Command\CommandInterface;
use Predis\Command\Processor\ProcessorInterface;

/**
 * A profile defines all the features and commands supported by certain versions
 * of Redis. Instances of Predis\Client should use a server profile matching the
 * version of Redis being used.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ProfileInterface
{
    /**
     * Returns the profile version corresponding to the Redis version.
     *
     * @return string
     */
    public function getVersion();

    /**
     * Checks if the profile supports the specified command.
     *
     * @param string $commandID Command ID.
     *
     * @return bool
     */
    public function supportsCommand($commandID);

    /**
     * Checks if the profile supports the specified list of commands.
     *
     * @param array $commandIDs List of command IDs.
     *
     * @return string
     */
    public function supportsCommands(array $commandIDs);

    /**
     * Creates a new command instance.
     *
     * @param string $commandID Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return CommandInterface
     */
    public function createCommand($commandID, array $arguments = array());
}

/**
 * Base class implementing common functionalities for Redis server profiles.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class RedisProfile implements ProfileInterface
{
    private $commands;
    private $processor;

    /**
     *
     */
    public function __construct()
    {
        $this->commands = $this->getSupportedCommands();
    }

    /**
     * Returns a map of all the commands supported by the profile and their
     * actual PHP classes.
     *
     * @return array
     */
    abstract protected function getSupportedCommands();

    /**
     * {@inheritdoc}
     */
    public function supportsCommand($commandID)
    {
        return isset($this->commands[strtoupper($commandID)]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCommands(array $commandIDs)
    {
        foreach ($commandIDs as $commandID) {
            if (!$this->supportsCommand($commandID)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the fully-qualified name of a class representing the specified
     * command ID registered in the current server profile.
     *
     * @param string $commandID Command ID.
     *
     * @return string|null
     */
    public function getCommandClass($commandID)
    {
        if (isset($this->commands[$commandID = strtoupper($commandID)])) {
            return $this->commands[$commandID];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($commandID, array $arguments = array())
    {
        $commandID = strtoupper($commandID);

        if (!isset($this->commands[$commandID])) {
            throw new ClientException("Command '$commandID' is not a registered Redis command.");
        }

        $commandClass = $this->commands[$commandID];
        $command = new $commandClass();
        $command->setArguments($arguments);

        if (isset($this->processor)) {
            $this->processor->process($command);
        }

        return $command;
    }

    /**
     * Defines a new command in the server profile.
     *
     * @param string $commandID Command ID.
     * @param string $class     Fully-qualified name of a Predis\Command\CommandInterface.
     *
     * @throws \InvalidArgumentException
     */
    public function defineCommand($commandID, $class)
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isSubclassOf('Predis\Command\CommandInterface')) {
            throw new \InvalidArgumentException("The class '$class' is not a valid command class.");
        }

        $this->commands[strtoupper($commandID)] = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(ProcessorInterface $processor = null)
    {
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Returns the version of server profile as its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getVersion();
    }
}

/**
 * Server profile for Redis 3.0.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisVersion300 extends RedisProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '3.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\KeyExists',
            'DEL' => 'Predis\Command\KeyDelete',
            'TYPE' => 'Predis\Command\KeyType',
            'KEYS' => 'Predis\Command\KeyKeys',
            'RANDOMKEY' => 'Predis\Command\KeyRandom',
            'RENAME' => 'Predis\Command\KeyRename',
            'RENAMENX' => 'Predis\Command\KeyRenamePreserve',
            'EXPIRE' => 'Predis\Command\KeyExpire',
            'EXPIREAT' => 'Predis\Command\KeyExpireAt',
            'TTL' => 'Predis\Command\KeyTimeToLive',
            'MOVE' => 'Predis\Command\KeyMove',
            'SORT' => 'Predis\Command\KeySort',
            'DUMP' => 'Predis\Command\KeyDump',
            'RESTORE' => 'Predis\Command\KeyRestore',

            /* commands operating on string values */
            'SET' => 'Predis\Command\StringSet',
            'SETNX' => 'Predis\Command\StringSetPreserve',
            'MSET' => 'Predis\Command\StringSetMultiple',
            'MSETNX' => 'Predis\Command\StringSetMultiplePreserve',
            'GET' => 'Predis\Command\StringGet',
            'MGET' => 'Predis\Command\StringGetMultiple',
            'GETSET' => 'Predis\Command\StringGetSet',
            'INCR' => 'Predis\Command\StringIncrement',
            'INCRBY' => 'Predis\Command\StringIncrementBy',
            'DECR' => 'Predis\Command\StringDecrement',
            'DECRBY' => 'Predis\Command\StringDecrementBy',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\ListPushTail',
            'LPUSH' => 'Predis\Command\ListPushHead',
            'LLEN' => 'Predis\Command\ListLength',
            'LRANGE' => 'Predis\Command\ListRange',
            'LTRIM' => 'Predis\Command\ListTrim',
            'LINDEX' => 'Predis\Command\ListIndex',
            'LSET' => 'Predis\Command\ListSet',
            'LREM' => 'Predis\Command\ListRemove',
            'LPOP' => 'Predis\Command\ListPopFirst',
            'RPOP' => 'Predis\Command\ListPopLast',
            'RPOPLPUSH' => 'Predis\Command\ListPopLastPushHead',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\SetAdd',
            'SREM' => 'Predis\Command\SetRemove',
            'SPOP' => 'Predis\Command\SetPop',
            'SMOVE' => 'Predis\Command\SetMove',
            'SCARD' => 'Predis\Command\SetCardinality',
            'SISMEMBER' => 'Predis\Command\SetIsMember',
            'SINTER' => 'Predis\Command\SetIntersection',
            'SINTERSTORE' => 'Predis\Command\SetIntersectionStore',
            'SUNION' => 'Predis\Command\SetUnion',
            'SUNIONSTORE' => 'Predis\Command\SetUnionStore',
            'SDIFF' => 'Predis\Command\SetDifference',
            'SDIFFSTORE' => 'Predis\Command\SetDifferenceStore',
            'SMEMBERS' => 'Predis\Command\SetMembers',
            'SRANDMEMBER' => 'Predis\Command\SetRandomMember',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\ZSetAdd',
            'ZINCRBY' => 'Predis\Command\ZSetIncrementBy',
            'ZREM' => 'Predis\Command\ZSetRemove',
            'ZRANGE' => 'Predis\Command\ZSetRange',
            'ZREVRANGE' => 'Predis\Command\ZSetReverseRange',
            'ZRANGEBYSCORE' => 'Predis\Command\ZSetRangeByScore',
            'ZCARD' => 'Predis\Command\ZSetCardinality',
            'ZSCORE' => 'Predis\Command\ZSetScore',
            'ZREMRANGEBYSCORE' => 'Predis\Command\ZSetRemoveRangeByScore',

            /* connection related commands */
            'PING' => 'Predis\Command\ConnectionPing',
            'AUTH' => 'Predis\Command\ConnectionAuth',
            'SELECT' => 'Predis\Command\ConnectionSelect',
            'ECHO' => 'Predis\Command\ConnectionEcho',
            'QUIT' => 'Predis\Command\ConnectionQuit',

            /* remote server control commands */
            'INFO' => 'Predis\Command\ServerInfoV26x',
            'SLAVEOF' => 'Predis\Command\ServerSlaveOf',
            'MONITOR' => 'Predis\Command\ServerMonitor',
            'DBSIZE' => 'Predis\Command\ServerDatabaseSize',
            'FLUSHDB' => 'Predis\Command\ServerFlushDatabase',
            'FLUSHALL' => 'Predis\Command\ServerFlushAll',
            'SAVE' => 'Predis\Command\ServerSave',
            'BGSAVE' => 'Predis\Command\ServerBackgroundSave',
            'LASTSAVE' => 'Predis\Command\ServerLastSave',
            'SHUTDOWN' => 'Predis\Command\ServerShutdown',
            'BGREWRITEAOF' => 'Predis\Command\ServerBackgroundRewriteAOF',

            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'SETEX' => 'Predis\Command\StringSetExpire',
            'APPEND' => 'Predis\Command\StringAppend',
            'SUBSTR' => 'Predis\Command\StringSubstr',

            /* commands operating on lists */
            'BLPOP' => 'Predis\Command\ListPopFirstBlocking',
            'BRPOP' => 'Predis\Command\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'ZUNIONSTORE' => 'Predis\Command\ZSetUnionStore',
            'ZINTERSTORE' => 'Predis\Command\ZSetIntersectionStore',
            'ZCOUNT' => 'Predis\Command\ZSetCount',
            'ZRANK' => 'Predis\Command\ZSetRank',
            'ZREVRANK' => 'Predis\Command\ZSetReverseRank',
            'ZREMRANGEBYRANK' => 'Predis\Command\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'HSET' => 'Predis\Command\HashSet',
            'HSETNX' => 'Predis\Command\HashSetPreserve',
            'HMSET' => 'Predis\Command\HashSetMultiple',
            'HINCRBY' => 'Predis\Command\HashIncrementBy',
            'HGET' => 'Predis\Command\HashGet',
            'HMGET' => 'Predis\Command\HashGetMultiple',
            'HDEL' => 'Predis\Command\HashDelete',
            'HEXISTS' => 'Predis\Command\HashExists',
            'HLEN' => 'Predis\Command\HashLength',
            'HKEYS' => 'Predis\Command\HashKeys',
            'HVALS' => 'Predis\Command\HashValues',
            'HGETALL' => 'Predis\Command\HashGetAll',

            /* transactions */
            'MULTI' => 'Predis\Command\TransactionMulti',
            'EXEC' => 'Predis\Command\TransactionExec',
            'DISCARD' => 'Predis\Command\TransactionDiscard',

            /* publish - subscribe */
            'SUBSCRIBE' => 'Predis\Command\PubSubSubscribe',
            'UNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribe',
            'PSUBSCRIBE' => 'Predis\Command\PubSubSubscribeByPattern',
            'PUNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribeByPattern',
            'PUBLISH' => 'Predis\Command\PubSubPublish',

            /* remote server control commands */
            'CONFIG' => 'Predis\Command\ServerConfig',

            /* ---------------- Redis 2.2 ---------------- */

            /* commands operating on the key space */
            'PERSIST' => 'Predis\Command\KeyPersist',

            /* commands operating on string values */
            'STRLEN' => 'Predis\Command\StringStrlen',
            'SETRANGE' => 'Predis\Command\StringSetRange',
            'GETRANGE' => 'Predis\Command\StringGetRange',
            'SETBIT' => 'Predis\Command\StringSetBit',
            'GETBIT' => 'Predis\Command\StringGetBit',

            /* commands operating on lists */
            'RPUSHX' => 'Predis\Command\ListPushTailX',
            'LPUSHX' => 'Predis\Command\ListPushHeadX',
            'LINSERT' => 'Predis\Command\ListInsert',
            'BRPOPLPUSH' => 'Predis\Command\ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'ZREVRANGEBYSCORE' => 'Predis\Command\ZSetReverseRangeByScore',

            /* transactions */
            'WATCH' => 'Predis\Command\TransactionWatch',
            'UNWATCH' => 'Predis\Command\TransactionUnwatch',

            /* remote server control commands */
            'OBJECT' => 'Predis\Command\ServerObject',
            'SLOWLOG' => 'Predis\Command\ServerSlowlog',

            /* ---------------- Redis 2.4 ---------------- */

            /* remote server control commands */
            'CLIENT' => 'Predis\Command\ServerClient',

            /* ---------------- Redis 2.6 ---------------- */

            /* commands operating on the key space */
            'PTTL' => 'Predis\Command\KeyPreciseTimeToLive',
            'PEXPIRE' => 'Predis\Command\KeyPreciseExpire',
            'PEXPIREAT' => 'Predis\Command\KeyPreciseExpireAt',
            'MIGRATE' => 'Predis\Command\KeyMigrate',

            /* commands operating on string values */
            'PSETEX' => 'Predis\Command\StringPreciseSetExpire',
            'INCRBYFLOAT' => 'Predis\Command\StringIncrementByFloat',
            'BITOP' => 'Predis\Command\StringBitOp',
            'BITCOUNT' => 'Predis\Command\StringBitCount',

            /* commands operating on hashes */
            'HINCRBYFLOAT' => 'Predis\Command\HashIncrementByFloat',

            /* scripting */
            'EVAL' => 'Predis\Command\ServerEval',
            'EVALSHA' => 'Predis\Command\ServerEvalSHA',
            'SCRIPT' => 'Predis\Command\ServerScript',

            /* remote server control commands */
            'TIME' => 'Predis\Command\ServerTime',
            'SENTINEL' => 'Predis\Command\ServerSentinel',

            /* ---------------- Redis 2.8 ---------------- */

            /* commands operating on the key space */
            'SCAN' => 'Predis\Command\KeyScan',

            /* commands operating on string values */
            'BITPOS' => 'Predis\Command\StringBitPos',

            /* commands operating on sets */
            'SSCAN' => 'Predis\Command\SetScan',

            /* commands operating on sorted sets */
            'ZSCAN' => 'Predis\Command\ZSetScan',
            'ZLEXCOUNT' => 'Predis\Command\ZSetLexCount',
            'ZRANGEBYLEX' => 'Predis\Command\ZSetRangeByLex',
            'ZREMRANGEBYLEX' => 'Predis\Command\ZSetRemoveRangeByLex',
            'ZREVRANGEBYLEX' => 'Predis\Command\ZSetReverseRangeByLex',

            /* commands operating on hashes */
            'HSCAN' => 'Predis\Command\HashScan',

            /* publish - subscribe */
            'PUBSUB' => 'Predis\Command\PubSubPubsub',

            /* commands operating on HyperLogLog */
            'PFADD' => 'Predis\Command\HyperLogLogAdd',
            'PFCOUNT' => 'Predis\Command\HyperLogLogCount',
            'PFMERGE' => 'Predis\Command\HyperLogLogMerge',

            /* remote server control commands */
            'COMMAND' => 'Predis\Command\ServerCommand',

            /* ---------------- Redis 3.0 ---------------- */

        );
    }
}

/**
 * Server profile for Redis 2.6.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisVersion260 extends RedisProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.6';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\KeyExists',
            'DEL' => 'Predis\Command\KeyDelete',
            'TYPE' => 'Predis\Command\KeyType',
            'KEYS' => 'Predis\Command\KeyKeys',
            'RANDOMKEY' => 'Predis\Command\KeyRandom',
            'RENAME' => 'Predis\Command\KeyRename',
            'RENAMENX' => 'Predis\Command\KeyRenamePreserve',
            'EXPIRE' => 'Predis\Command\KeyExpire',
            'EXPIREAT' => 'Predis\Command\KeyExpireAt',
            'TTL' => 'Predis\Command\KeyTimeToLive',
            'MOVE' => 'Predis\Command\KeyMove',
            'SORT' => 'Predis\Command\KeySort',
            'DUMP' => 'Predis\Command\KeyDump',
            'RESTORE' => 'Predis\Command\KeyRestore',

            /* commands operating on string values */
            'SET' => 'Predis\Command\StringSet',
            'SETNX' => 'Predis\Command\StringSetPreserve',
            'MSET' => 'Predis\Command\StringSetMultiple',
            'MSETNX' => 'Predis\Command\StringSetMultiplePreserve',
            'GET' => 'Predis\Command\StringGet',
            'MGET' => 'Predis\Command\StringGetMultiple',
            'GETSET' => 'Predis\Command\StringGetSet',
            'INCR' => 'Predis\Command\StringIncrement',
            'INCRBY' => 'Predis\Command\StringIncrementBy',
            'DECR' => 'Predis\Command\StringDecrement',
            'DECRBY' => 'Predis\Command\StringDecrementBy',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\ListPushTail',
            'LPUSH' => 'Predis\Command\ListPushHead',
            'LLEN' => 'Predis\Command\ListLength',
            'LRANGE' => 'Predis\Command\ListRange',
            'LTRIM' => 'Predis\Command\ListTrim',
            'LINDEX' => 'Predis\Command\ListIndex',
            'LSET' => 'Predis\Command\ListSet',
            'LREM' => 'Predis\Command\ListRemove',
            'LPOP' => 'Predis\Command\ListPopFirst',
            'RPOP' => 'Predis\Command\ListPopLast',
            'RPOPLPUSH' => 'Predis\Command\ListPopLastPushHead',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\SetAdd',
            'SREM' => 'Predis\Command\SetRemove',
            'SPOP' => 'Predis\Command\SetPop',
            'SMOVE' => 'Predis\Command\SetMove',
            'SCARD' => 'Predis\Command\SetCardinality',
            'SISMEMBER' => 'Predis\Command\SetIsMember',
            'SINTER' => 'Predis\Command\SetIntersection',
            'SINTERSTORE' => 'Predis\Command\SetIntersectionStore',
            'SUNION' => 'Predis\Command\SetUnion',
            'SUNIONSTORE' => 'Predis\Command\SetUnionStore',
            'SDIFF' => 'Predis\Command\SetDifference',
            'SDIFFSTORE' => 'Predis\Command\SetDifferenceStore',
            'SMEMBERS' => 'Predis\Command\SetMembers',
            'SRANDMEMBER' => 'Predis\Command\SetRandomMember',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\ZSetAdd',
            'ZINCRBY' => 'Predis\Command\ZSetIncrementBy',
            'ZREM' => 'Predis\Command\ZSetRemove',
            'ZRANGE' => 'Predis\Command\ZSetRange',
            'ZREVRANGE' => 'Predis\Command\ZSetReverseRange',
            'ZRANGEBYSCORE' => 'Predis\Command\ZSetRangeByScore',
            'ZCARD' => 'Predis\Command\ZSetCardinality',
            'ZSCORE' => 'Predis\Command\ZSetScore',
            'ZREMRANGEBYSCORE' => 'Predis\Command\ZSetRemoveRangeByScore',

            /* connection related commands */
            'PING' => 'Predis\Command\ConnectionPing',
            'AUTH' => 'Predis\Command\ConnectionAuth',
            'SELECT' => 'Predis\Command\ConnectionSelect',
            'ECHO' => 'Predis\Command\ConnectionEcho',
            'QUIT' => 'Predis\Command\ConnectionQuit',

            /* remote server control commands */
            'INFO' => 'Predis\Command\ServerInfoV26x',
            'SLAVEOF' => 'Predis\Command\ServerSlaveOf',
            'MONITOR' => 'Predis\Command\ServerMonitor',
            'DBSIZE' => 'Predis\Command\ServerDatabaseSize',
            'FLUSHDB' => 'Predis\Command\ServerFlushDatabase',
            'FLUSHALL' => 'Predis\Command\ServerFlushAll',
            'SAVE' => 'Predis\Command\ServerSave',
            'BGSAVE' => 'Predis\Command\ServerBackgroundSave',
            'LASTSAVE' => 'Predis\Command\ServerLastSave',
            'SHUTDOWN' => 'Predis\Command\ServerShutdown',
            'BGREWRITEAOF' => 'Predis\Command\ServerBackgroundRewriteAOF',

            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'SETEX' => 'Predis\Command\StringSetExpire',
            'APPEND' => 'Predis\Command\StringAppend',
            'SUBSTR' => 'Predis\Command\StringSubstr',

            /* commands operating on lists */
            'BLPOP' => 'Predis\Command\ListPopFirstBlocking',
            'BRPOP' => 'Predis\Command\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'ZUNIONSTORE' => 'Predis\Command\ZSetUnionStore',
            'ZINTERSTORE' => 'Predis\Command\ZSetIntersectionStore',
            'ZCOUNT' => 'Predis\Command\ZSetCount',
            'ZRANK' => 'Predis\Command\ZSetRank',
            'ZREVRANK' => 'Predis\Command\ZSetReverseRank',
            'ZREMRANGEBYRANK' => 'Predis\Command\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'HSET' => 'Predis\Command\HashSet',
            'HSETNX' => 'Predis\Command\HashSetPreserve',
            'HMSET' => 'Predis\Command\HashSetMultiple',
            'HINCRBY' => 'Predis\Command\HashIncrementBy',
            'HGET' => 'Predis\Command\HashGet',
            'HMGET' => 'Predis\Command\HashGetMultiple',
            'HDEL' => 'Predis\Command\HashDelete',
            'HEXISTS' => 'Predis\Command\HashExists',
            'HLEN' => 'Predis\Command\HashLength',
            'HKEYS' => 'Predis\Command\HashKeys',
            'HVALS' => 'Predis\Command\HashValues',
            'HGETALL' => 'Predis\Command\HashGetAll',

            /* transactions */
            'MULTI' => 'Predis\Command\TransactionMulti',
            'EXEC' => 'Predis\Command\TransactionExec',
            'DISCARD' => 'Predis\Command\TransactionDiscard',

            /* publish - subscribe */
            'SUBSCRIBE' => 'Predis\Command\PubSubSubscribe',
            'UNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribe',
            'PSUBSCRIBE' => 'Predis\Command\PubSubSubscribeByPattern',
            'PUNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribeByPattern',
            'PUBLISH' => 'Predis\Command\PubSubPublish',

            /* remote server control commands */
            'CONFIG' => 'Predis\Command\ServerConfig',

            /* ---------------- Redis 2.2 ---------------- */

            /* commands operating on the key space */
            'PERSIST' => 'Predis\Command\KeyPersist',

            /* commands operating on string values */
            'STRLEN' => 'Predis\Command\StringStrlen',
            'SETRANGE' => 'Predis\Command\StringSetRange',
            'GETRANGE' => 'Predis\Command\StringGetRange',
            'SETBIT' => 'Predis\Command\StringSetBit',
            'GETBIT' => 'Predis\Command\StringGetBit',

            /* commands operating on lists */
            'RPUSHX' => 'Predis\Command\ListPushTailX',
            'LPUSHX' => 'Predis\Command\ListPushHeadX',
            'LINSERT' => 'Predis\Command\ListInsert',
            'BRPOPLPUSH' => 'Predis\Command\ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'ZREVRANGEBYSCORE' => 'Predis\Command\ZSetReverseRangeByScore',

            /* transactions */
            'WATCH' => 'Predis\Command\TransactionWatch',
            'UNWATCH' => 'Predis\Command\TransactionUnwatch',

            /* remote server control commands */
            'OBJECT' => 'Predis\Command\ServerObject',
            'SLOWLOG' => 'Predis\Command\ServerSlowlog',

            /* ---------------- Redis 2.4 ---------------- */

            /* remote server control commands */
            'CLIENT' => 'Predis\Command\ServerClient',

            /* ---------------- Redis 2.6 ---------------- */

            /* commands operating on the key space */
            'PTTL' => 'Predis\Command\KeyPreciseTimeToLive',
            'PEXPIRE' => 'Predis\Command\KeyPreciseExpire',
            'PEXPIREAT' => 'Predis\Command\KeyPreciseExpireAt',
            'MIGRATE' => 'Predis\Command\KeyMigrate',

            /* commands operating on string values */
            'PSETEX' => 'Predis\Command\StringPreciseSetExpire',
            'INCRBYFLOAT' => 'Predis\Command\StringIncrementByFloat',
            'BITOP' => 'Predis\Command\StringBitOp',
            'BITCOUNT' => 'Predis\Command\StringBitCount',

            /* commands operating on hashes */
            'HINCRBYFLOAT' => 'Predis\Command\HashIncrementByFloat',

            /* scripting */
            'EVAL' => 'Predis\Command\ServerEval',
            'EVALSHA' => 'Predis\Command\ServerEvalSHA',
            'SCRIPT' => 'Predis\Command\ServerScript',

            /* remote server control commands */
            'TIME' => 'Predis\Command\ServerTime',
            'SENTINEL' => 'Predis\Command\ServerSentinel',
        );
    }
}

/**
 * Server profile for Redis 2.8.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisVersion280 extends RedisProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.8';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\KeyExists',
            'DEL' => 'Predis\Command\KeyDelete',
            'TYPE' => 'Predis\Command\KeyType',
            'KEYS' => 'Predis\Command\KeyKeys',
            'RANDOMKEY' => 'Predis\Command\KeyRandom',
            'RENAME' => 'Predis\Command\KeyRename',
            'RENAMENX' => 'Predis\Command\KeyRenamePreserve',
            'EXPIRE' => 'Predis\Command\KeyExpire',
            'EXPIREAT' => 'Predis\Command\KeyExpireAt',
            'TTL' => 'Predis\Command\KeyTimeToLive',
            'MOVE' => 'Predis\Command\KeyMove',
            'SORT' => 'Predis\Command\KeySort',
            'DUMP' => 'Predis\Command\KeyDump',
            'RESTORE' => 'Predis\Command\KeyRestore',

            /* commands operating on string values */
            'SET' => 'Predis\Command\StringSet',
            'SETNX' => 'Predis\Command\StringSetPreserve',
            'MSET' => 'Predis\Command\StringSetMultiple',
            'MSETNX' => 'Predis\Command\StringSetMultiplePreserve',
            'GET' => 'Predis\Command\StringGet',
            'MGET' => 'Predis\Command\StringGetMultiple',
            'GETSET' => 'Predis\Command\StringGetSet',
            'INCR' => 'Predis\Command\StringIncrement',
            'INCRBY' => 'Predis\Command\StringIncrementBy',
            'DECR' => 'Predis\Command\StringDecrement',
            'DECRBY' => 'Predis\Command\StringDecrementBy',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\ListPushTail',
            'LPUSH' => 'Predis\Command\ListPushHead',
            'LLEN' => 'Predis\Command\ListLength',
            'LRANGE' => 'Predis\Command\ListRange',
            'LTRIM' => 'Predis\Command\ListTrim',
            'LINDEX' => 'Predis\Command\ListIndex',
            'LSET' => 'Predis\Command\ListSet',
            'LREM' => 'Predis\Command\ListRemove',
            'LPOP' => 'Predis\Command\ListPopFirst',
            'RPOP' => 'Predis\Command\ListPopLast',
            'RPOPLPUSH' => 'Predis\Command\ListPopLastPushHead',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\SetAdd',
            'SREM' => 'Predis\Command\SetRemove',
            'SPOP' => 'Predis\Command\SetPop',
            'SMOVE' => 'Predis\Command\SetMove',
            'SCARD' => 'Predis\Command\SetCardinality',
            'SISMEMBER' => 'Predis\Command\SetIsMember',
            'SINTER' => 'Predis\Command\SetIntersection',
            'SINTERSTORE' => 'Predis\Command\SetIntersectionStore',
            'SUNION' => 'Predis\Command\SetUnion',
            'SUNIONSTORE' => 'Predis\Command\SetUnionStore',
            'SDIFF' => 'Predis\Command\SetDifference',
            'SDIFFSTORE' => 'Predis\Command\SetDifferenceStore',
            'SMEMBERS' => 'Predis\Command\SetMembers',
            'SRANDMEMBER' => 'Predis\Command\SetRandomMember',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\ZSetAdd',
            'ZINCRBY' => 'Predis\Command\ZSetIncrementBy',
            'ZREM' => 'Predis\Command\ZSetRemove',
            'ZRANGE' => 'Predis\Command\ZSetRange',
            'ZREVRANGE' => 'Predis\Command\ZSetReverseRange',
            'ZRANGEBYSCORE' => 'Predis\Command\ZSetRangeByScore',
            'ZCARD' => 'Predis\Command\ZSetCardinality',
            'ZSCORE' => 'Predis\Command\ZSetScore',
            'ZREMRANGEBYSCORE' => 'Predis\Command\ZSetRemoveRangeByScore',

            /* connection related commands */
            'PING' => 'Predis\Command\ConnectionPing',
            'AUTH' => 'Predis\Command\ConnectionAuth',
            'SELECT' => 'Predis\Command\ConnectionSelect',
            'ECHO' => 'Predis\Command\ConnectionEcho',
            'QUIT' => 'Predis\Command\ConnectionQuit',

            /* remote server control commands */
            'INFO' => 'Predis\Command\ServerInfoV26x',
            'SLAVEOF' => 'Predis\Command\ServerSlaveOf',
            'MONITOR' => 'Predis\Command\ServerMonitor',
            'DBSIZE' => 'Predis\Command\ServerDatabaseSize',
            'FLUSHDB' => 'Predis\Command\ServerFlushDatabase',
            'FLUSHALL' => 'Predis\Command\ServerFlushAll',
            'SAVE' => 'Predis\Command\ServerSave',
            'BGSAVE' => 'Predis\Command\ServerBackgroundSave',
            'LASTSAVE' => 'Predis\Command\ServerLastSave',
            'SHUTDOWN' => 'Predis\Command\ServerShutdown',
            'BGREWRITEAOF' => 'Predis\Command\ServerBackgroundRewriteAOF',

            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'SETEX' => 'Predis\Command\StringSetExpire',
            'APPEND' => 'Predis\Command\StringAppend',
            'SUBSTR' => 'Predis\Command\StringSubstr',

            /* commands operating on lists */
            'BLPOP' => 'Predis\Command\ListPopFirstBlocking',
            'BRPOP' => 'Predis\Command\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'ZUNIONSTORE' => 'Predis\Command\ZSetUnionStore',
            'ZINTERSTORE' => 'Predis\Command\ZSetIntersectionStore',
            'ZCOUNT' => 'Predis\Command\ZSetCount',
            'ZRANK' => 'Predis\Command\ZSetRank',
            'ZREVRANK' => 'Predis\Command\ZSetReverseRank',
            'ZREMRANGEBYRANK' => 'Predis\Command\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'HSET' => 'Predis\Command\HashSet',
            'HSETNX' => 'Predis\Command\HashSetPreserve',
            'HMSET' => 'Predis\Command\HashSetMultiple',
            'HINCRBY' => 'Predis\Command\HashIncrementBy',
            'HGET' => 'Predis\Command\HashGet',
            'HMGET' => 'Predis\Command\HashGetMultiple',
            'HDEL' => 'Predis\Command\HashDelete',
            'HEXISTS' => 'Predis\Command\HashExists',
            'HLEN' => 'Predis\Command\HashLength',
            'HKEYS' => 'Predis\Command\HashKeys',
            'HVALS' => 'Predis\Command\HashValues',
            'HGETALL' => 'Predis\Command\HashGetAll',

            /* transactions */
            'MULTI' => 'Predis\Command\TransactionMulti',
            'EXEC' => 'Predis\Command\TransactionExec',
            'DISCARD' => 'Predis\Command\TransactionDiscard',

            /* publish - subscribe */
            'SUBSCRIBE' => 'Predis\Command\PubSubSubscribe',
            'UNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribe',
            'PSUBSCRIBE' => 'Predis\Command\PubSubSubscribeByPattern',
            'PUNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribeByPattern',
            'PUBLISH' => 'Predis\Command\PubSubPublish',

            /* remote server control commands */
            'CONFIG' => 'Predis\Command\ServerConfig',

            /* ---------------- Redis 2.2 ---------------- */

            /* commands operating on the key space */
            'PERSIST' => 'Predis\Command\KeyPersist',

            /* commands operating on string values */
            'STRLEN' => 'Predis\Command\StringStrlen',
            'SETRANGE' => 'Predis\Command\StringSetRange',
            'GETRANGE' => 'Predis\Command\StringGetRange',
            'SETBIT' => 'Predis\Command\StringSetBit',
            'GETBIT' => 'Predis\Command\StringGetBit',

            /* commands operating on lists */
            'RPUSHX' => 'Predis\Command\ListPushTailX',
            'LPUSHX' => 'Predis\Command\ListPushHeadX',
            'LINSERT' => 'Predis\Command\ListInsert',
            'BRPOPLPUSH' => 'Predis\Command\ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'ZREVRANGEBYSCORE' => 'Predis\Command\ZSetReverseRangeByScore',

            /* transactions */
            'WATCH' => 'Predis\Command\TransactionWatch',
            'UNWATCH' => 'Predis\Command\TransactionUnwatch',

            /* remote server control commands */
            'OBJECT' => 'Predis\Command\ServerObject',
            'SLOWLOG' => 'Predis\Command\ServerSlowlog',

            /* ---------------- Redis 2.4 ---------------- */

            /* remote server control commands */
            'CLIENT' => 'Predis\Command\ServerClient',

            /* ---------------- Redis 2.6 ---------------- */

            /* commands operating on the key space */
            'PTTL' => 'Predis\Command\KeyPreciseTimeToLive',
            'PEXPIRE' => 'Predis\Command\KeyPreciseExpire',
            'PEXPIREAT' => 'Predis\Command\KeyPreciseExpireAt',
            'MIGRATE' => 'Predis\Command\KeyMigrate',

            /* commands operating on string values */
            'PSETEX' => 'Predis\Command\StringPreciseSetExpire',
            'INCRBYFLOAT' => 'Predis\Command\StringIncrementByFloat',
            'BITOP' => 'Predis\Command\StringBitOp',
            'BITCOUNT' => 'Predis\Command\StringBitCount',

            /* commands operating on hashes */
            'HINCRBYFLOAT' => 'Predis\Command\HashIncrementByFloat',

            /* scripting */
            'EVAL' => 'Predis\Command\ServerEval',
            'EVALSHA' => 'Predis\Command\ServerEvalSHA',
            'SCRIPT' => 'Predis\Command\ServerScript',

            /* remote server control commands */
            'TIME' => 'Predis\Command\ServerTime',
            'SENTINEL' => 'Predis\Command\ServerSentinel',

            /* ---------------- Redis 2.8 ---------------- */

            /* commands operating on the key space */
            'SCAN' => 'Predis\Command\KeyScan',

            /* commands operating on string values */
            'BITPOS' => 'Predis\Command\StringBitPos',

            /* commands operating on sets */
            'SSCAN' => 'Predis\Command\SetScan',

            /* commands operating on sorted sets */
            'ZSCAN' => 'Predis\Command\ZSetScan',
            'ZLEXCOUNT' => 'Predis\Command\ZSetLexCount',
            'ZRANGEBYLEX' => 'Predis\Command\ZSetRangeByLex',
            'ZREMRANGEBYLEX' => 'Predis\Command\ZSetRemoveRangeByLex',
            'ZREVRANGEBYLEX' => 'Predis\Command\ZSetReverseRangeByLex',

            /* commands operating on hashes */
            'HSCAN' => 'Predis\Command\HashScan',

            /* publish - subscribe */
            'PUBSUB' => 'Predis\Command\PubSubPubsub',

            /* commands operating on HyperLogLog */
            'PFADD' => 'Predis\Command\HyperLogLogAdd',
            'PFCOUNT' => 'Predis\Command\HyperLogLogCount',
            'PFMERGE' => 'Predis\Command\HyperLogLogMerge',

            /* remote server control commands */
            'COMMAND' => 'Predis\Command\ServerCommand',
        );
    }
}

/**
 * Server profile for Redis 2.4.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisVersion240 extends RedisProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.4';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\KeyExists',
            'DEL' => 'Predis\Command\KeyDelete',
            'TYPE' => 'Predis\Command\KeyType',
            'KEYS' => 'Predis\Command\KeyKeys',
            'RANDOMKEY' => 'Predis\Command\KeyRandom',
            'RENAME' => 'Predis\Command\KeyRename',
            'RENAMENX' => 'Predis\Command\KeyRenamePreserve',
            'EXPIRE' => 'Predis\Command\KeyExpire',
            'EXPIREAT' => 'Predis\Command\KeyExpireAt',
            'TTL' => 'Predis\Command\KeyTimeToLive',
            'MOVE' => 'Predis\Command\KeyMove',
            'SORT' => 'Predis\Command\KeySort',

            /* commands operating on string values */
            'SET' => 'Predis\Command\StringSet',
            'SETNX' => 'Predis\Command\StringSetPreserve',
            'MSET' => 'Predis\Command\StringSetMultiple',
            'MSETNX' => 'Predis\Command\StringSetMultiplePreserve',
            'GET' => 'Predis\Command\StringGet',
            'MGET' => 'Predis\Command\StringGetMultiple',
            'GETSET' => 'Predis\Command\StringGetSet',
            'INCR' => 'Predis\Command\StringIncrement',
            'INCRBY' => 'Predis\Command\StringIncrementBy',
            'DECR' => 'Predis\Command\StringDecrement',
            'DECRBY' => 'Predis\Command\StringDecrementBy',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\ListPushTail',
            'LPUSH' => 'Predis\Command\ListPushHead',
            'LLEN' => 'Predis\Command\ListLength',
            'LRANGE' => 'Predis\Command\ListRange',
            'LTRIM' => 'Predis\Command\ListTrim',
            'LINDEX' => 'Predis\Command\ListIndex',
            'LSET' => 'Predis\Command\ListSet',
            'LREM' => 'Predis\Command\ListRemove',
            'LPOP' => 'Predis\Command\ListPopFirst',
            'RPOP' => 'Predis\Command\ListPopLast',
            'RPOPLPUSH' => 'Predis\Command\ListPopLastPushHead',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\SetAdd',
            'SREM' => 'Predis\Command\SetRemove',
            'SPOP' => 'Predis\Command\SetPop',
            'SMOVE' => 'Predis\Command\SetMove',
            'SCARD' => 'Predis\Command\SetCardinality',
            'SISMEMBER' => 'Predis\Command\SetIsMember',
            'SINTER' => 'Predis\Command\SetIntersection',
            'SINTERSTORE' => 'Predis\Command\SetIntersectionStore',
            'SUNION' => 'Predis\Command\SetUnion',
            'SUNIONSTORE' => 'Predis\Command\SetUnionStore',
            'SDIFF' => 'Predis\Command\SetDifference',
            'SDIFFSTORE' => 'Predis\Command\SetDifferenceStore',
            'SMEMBERS' => 'Predis\Command\SetMembers',
            'SRANDMEMBER' => 'Predis\Command\SetRandomMember',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\ZSetAdd',
            'ZINCRBY' => 'Predis\Command\ZSetIncrementBy',
            'ZREM' => 'Predis\Command\ZSetRemove',
            'ZRANGE' => 'Predis\Command\ZSetRange',
            'ZREVRANGE' => 'Predis\Command\ZSetReverseRange',
            'ZRANGEBYSCORE' => 'Predis\Command\ZSetRangeByScore',
            'ZCARD' => 'Predis\Command\ZSetCardinality',
            'ZSCORE' => 'Predis\Command\ZSetScore',
            'ZREMRANGEBYSCORE' => 'Predis\Command\ZSetRemoveRangeByScore',

            /* connection related commands */
            'PING' => 'Predis\Command\ConnectionPing',
            'AUTH' => 'Predis\Command\ConnectionAuth',
            'SELECT' => 'Predis\Command\ConnectionSelect',
            'ECHO' => 'Predis\Command\ConnectionEcho',
            'QUIT' => 'Predis\Command\ConnectionQuit',

            /* remote server control commands */
            'INFO' => 'Predis\Command\ServerInfo',
            'SLAVEOF' => 'Predis\Command\ServerSlaveOf',
            'MONITOR' => 'Predis\Command\ServerMonitor',
            'DBSIZE' => 'Predis\Command\ServerDatabaseSize',
            'FLUSHDB' => 'Predis\Command\ServerFlushDatabase',
            'FLUSHALL' => 'Predis\Command\ServerFlushAll',
            'SAVE' => 'Predis\Command\ServerSave',
            'BGSAVE' => 'Predis\Command\ServerBackgroundSave',
            'LASTSAVE' => 'Predis\Command\ServerLastSave',
            'SHUTDOWN' => 'Predis\Command\ServerShutdown',
            'BGREWRITEAOF' => 'Predis\Command\ServerBackgroundRewriteAOF',

            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'SETEX' => 'Predis\Command\StringSetExpire',
            'APPEND' => 'Predis\Command\StringAppend',
            'SUBSTR' => 'Predis\Command\StringSubstr',

            /* commands operating on lists */
            'BLPOP' => 'Predis\Command\ListPopFirstBlocking',
            'BRPOP' => 'Predis\Command\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'ZUNIONSTORE' => 'Predis\Command\ZSetUnionStore',
            'ZINTERSTORE' => 'Predis\Command\ZSetIntersectionStore',
            'ZCOUNT' => 'Predis\Command\ZSetCount',
            'ZRANK' => 'Predis\Command\ZSetRank',
            'ZREVRANK' => 'Predis\Command\ZSetReverseRank',
            'ZREMRANGEBYRANK' => 'Predis\Command\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'HSET' => 'Predis\Command\HashSet',
            'HSETNX' => 'Predis\Command\HashSetPreserve',
            'HMSET' => 'Predis\Command\HashSetMultiple',
            'HINCRBY' => 'Predis\Command\HashIncrementBy',
            'HGET' => 'Predis\Command\HashGet',
            'HMGET' => 'Predis\Command\HashGetMultiple',
            'HDEL' => 'Predis\Command\HashDelete',
            'HEXISTS' => 'Predis\Command\HashExists',
            'HLEN' => 'Predis\Command\HashLength',
            'HKEYS' => 'Predis\Command\HashKeys',
            'HVALS' => 'Predis\Command\HashValues',
            'HGETALL' => 'Predis\Command\HashGetAll',

            /* transactions */
            'MULTI' => 'Predis\Command\TransactionMulti',
            'EXEC' => 'Predis\Command\TransactionExec',
            'DISCARD' => 'Predis\Command\TransactionDiscard',

            /* publish - subscribe */
            'SUBSCRIBE' => 'Predis\Command\PubSubSubscribe',
            'UNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribe',
            'PSUBSCRIBE' => 'Predis\Command\PubSubSubscribeByPattern',
            'PUNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribeByPattern',
            'PUBLISH' => 'Predis\Command\PubSubPublish',

            /* remote server control commands */
            'CONFIG' => 'Predis\Command\ServerConfig',

            /* ---------------- Redis 2.2 ---------------- */

            /* commands operating on the key space */
            'PERSIST' => 'Predis\Command\KeyPersist',

            /* commands operating on string values */
            'STRLEN' => 'Predis\Command\StringStrlen',
            'SETRANGE' => 'Predis\Command\StringSetRange',
            'GETRANGE' => 'Predis\Command\StringGetRange',
            'SETBIT' => 'Predis\Command\StringSetBit',
            'GETBIT' => 'Predis\Command\StringGetBit',

            /* commands operating on lists */
            'RPUSHX' => 'Predis\Command\ListPushTailX',
            'LPUSHX' => 'Predis\Command\ListPushHeadX',
            'LINSERT' => 'Predis\Command\ListInsert',
            'BRPOPLPUSH' => 'Predis\Command\ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'ZREVRANGEBYSCORE' => 'Predis\Command\ZSetReverseRangeByScore',

            /* transactions */
            'WATCH' => 'Predis\Command\TransactionWatch',
            'UNWATCH' => 'Predis\Command\TransactionUnwatch',

            /* remote server control commands */
            'OBJECT' => 'Predis\Command\ServerObject',
            'SLOWLOG' => 'Predis\Command\ServerSlowlog',

            /* ---------------- Redis 2.4 ---------------- */

            /* remote server control commands */
            'CLIENT' => 'Predis\Command\ServerClient',
        );
    }
}

/**
 * Server profile for Redis 2.0.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisVersion200 extends RedisProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\KeyExists',
            'DEL' => 'Predis\Command\KeyDelete',
            'TYPE' => 'Predis\Command\KeyType',
            'KEYS' => 'Predis\Command\KeyKeys',
            'RANDOMKEY' => 'Predis\Command\KeyRandom',
            'RENAME' => 'Predis\Command\KeyRename',
            'RENAMENX' => 'Predis\Command\KeyRenamePreserve',
            'EXPIRE' => 'Predis\Command\KeyExpire',
            'EXPIREAT' => 'Predis\Command\KeyExpireAt',
            'TTL' => 'Predis\Command\KeyTimeToLive',
            'MOVE' => 'Predis\Command\KeyMove',
            'SORT' => 'Predis\Command\KeySort',

            /* commands operating on string values */
            'SET' => 'Predis\Command\StringSet',
            'SETNX' => 'Predis\Command\StringSetPreserve',
            'MSET' => 'Predis\Command\StringSetMultiple',
            'MSETNX' => 'Predis\Command\StringSetMultiplePreserve',
            'GET' => 'Predis\Command\StringGet',
            'MGET' => 'Predis\Command\StringGetMultiple',
            'GETSET' => 'Predis\Command\StringGetSet',
            'INCR' => 'Predis\Command\StringIncrement',
            'INCRBY' => 'Predis\Command\StringIncrementBy',
            'DECR' => 'Predis\Command\StringDecrement',
            'DECRBY' => 'Predis\Command\StringDecrementBy',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\ListPushTail',
            'LPUSH' => 'Predis\Command\ListPushHead',
            'LLEN' => 'Predis\Command\ListLength',
            'LRANGE' => 'Predis\Command\ListRange',
            'LTRIM' => 'Predis\Command\ListTrim',
            'LINDEX' => 'Predis\Command\ListIndex',
            'LSET' => 'Predis\Command\ListSet',
            'LREM' => 'Predis\Command\ListRemove',
            'LPOP' => 'Predis\Command\ListPopFirst',
            'RPOP' => 'Predis\Command\ListPopLast',
            'RPOPLPUSH' => 'Predis\Command\ListPopLastPushHead',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\SetAdd',
            'SREM' => 'Predis\Command\SetRemove',
            'SPOP' => 'Predis\Command\SetPop',
            'SMOVE' => 'Predis\Command\SetMove',
            'SCARD' => 'Predis\Command\SetCardinality',
            'SISMEMBER' => 'Predis\Command\SetIsMember',
            'SINTER' => 'Predis\Command\SetIntersection',
            'SINTERSTORE' => 'Predis\Command\SetIntersectionStore',
            'SUNION' => 'Predis\Command\SetUnion',
            'SUNIONSTORE' => 'Predis\Command\SetUnionStore',
            'SDIFF' => 'Predis\Command\SetDifference',
            'SDIFFSTORE' => 'Predis\Command\SetDifferenceStore',
            'SMEMBERS' => 'Predis\Command\SetMembers',
            'SRANDMEMBER' => 'Predis\Command\SetRandomMember',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\ZSetAdd',
            'ZINCRBY' => 'Predis\Command\ZSetIncrementBy',
            'ZREM' => 'Predis\Command\ZSetRemove',
            'ZRANGE' => 'Predis\Command\ZSetRange',
            'ZREVRANGE' => 'Predis\Command\ZSetReverseRange',
            'ZRANGEBYSCORE' => 'Predis\Command\ZSetRangeByScore',
            'ZCARD' => 'Predis\Command\ZSetCardinality',
            'ZSCORE' => 'Predis\Command\ZSetScore',
            'ZREMRANGEBYSCORE' => 'Predis\Command\ZSetRemoveRangeByScore',

            /* connection related commands */
            'PING' => 'Predis\Command\ConnectionPing',
            'AUTH' => 'Predis\Command\ConnectionAuth',
            'SELECT' => 'Predis\Command\ConnectionSelect',
            'ECHO' => 'Predis\Command\ConnectionEcho',
            'QUIT' => 'Predis\Command\ConnectionQuit',

            /* remote server control commands */
            'INFO' => 'Predis\Command\ServerInfo',
            'SLAVEOF' => 'Predis\Command\ServerSlaveOf',
            'MONITOR' => 'Predis\Command\ServerMonitor',
            'DBSIZE' => 'Predis\Command\ServerDatabaseSize',
            'FLUSHDB' => 'Predis\Command\ServerFlushDatabase',
            'FLUSHALL' => 'Predis\Command\ServerFlushAll',
            'SAVE' => 'Predis\Command\ServerSave',
            'BGSAVE' => 'Predis\Command\ServerBackgroundSave',
            'LASTSAVE' => 'Predis\Command\ServerLastSave',
            'SHUTDOWN' => 'Predis\Command\ServerShutdown',
            'BGREWRITEAOF' => 'Predis\Command\ServerBackgroundRewriteAOF',

            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'SETEX' => 'Predis\Command\StringSetExpire',
            'APPEND' => 'Predis\Command\StringAppend',
            'SUBSTR' => 'Predis\Command\StringSubstr',

            /* commands operating on lists */
            'BLPOP' => 'Predis\Command\ListPopFirstBlocking',
            'BRPOP' => 'Predis\Command\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'ZUNIONSTORE' => 'Predis\Command\ZSetUnionStore',
            'ZINTERSTORE' => 'Predis\Command\ZSetIntersectionStore',
            'ZCOUNT' => 'Predis\Command\ZSetCount',
            'ZRANK' => 'Predis\Command\ZSetRank',
            'ZREVRANK' => 'Predis\Command\ZSetReverseRank',
            'ZREMRANGEBYRANK' => 'Predis\Command\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'HSET' => 'Predis\Command\HashSet',
            'HSETNX' => 'Predis\Command\HashSetPreserve',
            'HMSET' => 'Predis\Command\HashSetMultiple',
            'HINCRBY' => 'Predis\Command\HashIncrementBy',
            'HGET' => 'Predis\Command\HashGet',
            'HMGET' => 'Predis\Command\HashGetMultiple',
            'HDEL' => 'Predis\Command\HashDelete',
            'HEXISTS' => 'Predis\Command\HashExists',
            'HLEN' => 'Predis\Command\HashLength',
            'HKEYS' => 'Predis\Command\HashKeys',
            'HVALS' => 'Predis\Command\HashValues',
            'HGETALL' => 'Predis\Command\HashGetAll',

            /* transactions */
            'MULTI' => 'Predis\Command\TransactionMulti',
            'EXEC' => 'Predis\Command\TransactionExec',
            'DISCARD' => 'Predis\Command\TransactionDiscard',

            /* publish - subscribe */
            'SUBSCRIBE' => 'Predis\Command\PubSubSubscribe',
            'UNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribe',
            'PSUBSCRIBE' => 'Predis\Command\PubSubSubscribeByPattern',
            'PUNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribeByPattern',
            'PUBLISH' => 'Predis\Command\PubSubPublish',

            /* remote server control commands */
            'CONFIG' => 'Predis\Command\ServerConfig',
        );
    }
}

/**
 * Server profile for the current unstable version of Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisUnstable extends RedisVersion300
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '3.2';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array_merge(parent::getSupportedCommands(), array(
            /* ---------------- Redis 3.2 ---------------- */

            /* commands operating on hashes */
            'HSTRLEN' => 'Predis\Command\HashStringLength',
        ));
    }
}

/**
 * Factory class for creating profile instances from strings.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
final class Factory
{
    private static $profiles = array(
        '2.0' => 'Predis\Profile\RedisVersion200',
        '2.2' => 'Predis\Profile\RedisVersion220',
        '2.4' => 'Predis\Profile\RedisVersion240',
        '2.6' => 'Predis\Profile\RedisVersion260',
        '2.8' => 'Predis\Profile\RedisVersion280',
        '3.0' => 'Predis\Profile\RedisVersion300',
        'dev' => 'Predis\Profile\RedisUnstable',
        'default' => 'Predis\Profile\RedisVersion300',
    );

    /**
     *
     */
    private function __construct()
    {
        // NOOP
    }

    /**
     * Returns the default server profile.
     *
     * @return ProfileInterface
     */
    public static function getDefault()
    {
        return self::get('default');
    }

    /**
     * Returns the development server profile.
     *
     * @return ProfileInterface
     */
    public static function getDevelopment()
    {
        return self::get('dev');
    }

    /**
     * Registers a new server profile.
     *
     * @param string $alias Profile version or alias.
     * @param string $class FQN of a class implementing Predis\Profile\ProfileInterface.
     *
     * @throws \InvalidArgumentException
     */
    public static function define($alias, $class)
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isSubclassOf('Predis\Profile\ProfileInterface')) {
            throw new \InvalidArgumentException("The class '$class' is not a valid profile class.");
        }

        self::$profiles[$alias] = $class;
    }

    /**
     * Returns the specified server profile.
     *
     * @param string $version Profile version or alias.
     *
     * @throws ClientException
     *
     * @return ProfileInterface
     */
    public static function get($version)
    {
        if (!isset(self::$profiles[$version])) {
            throw new ClientException("Unknown server profile: '$version'.");
        }

        $profile = self::$profiles[$version];

        return new $profile();
    }
}

/**
 * Server profile for Redis 2.2.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisVersion220 extends RedisProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.2';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\KeyExists',
            'DEL' => 'Predis\Command\KeyDelete',
            'TYPE' => 'Predis\Command\KeyType',
            'KEYS' => 'Predis\Command\KeyKeys',
            'RANDOMKEY' => 'Predis\Command\KeyRandom',
            'RENAME' => 'Predis\Command\KeyRename',
            'RENAMENX' => 'Predis\Command\KeyRenamePreserve',
            'EXPIRE' => 'Predis\Command\KeyExpire',
            'EXPIREAT' => 'Predis\Command\KeyExpireAt',
            'TTL' => 'Predis\Command\KeyTimeToLive',
            'MOVE' => 'Predis\Command\KeyMove',
            'SORT' => 'Predis\Command\KeySort',

            /* commands operating on string values */
            'SET' => 'Predis\Command\StringSet',
            'SETNX' => 'Predis\Command\StringSetPreserve',
            'MSET' => 'Predis\Command\StringSetMultiple',
            'MSETNX' => 'Predis\Command\StringSetMultiplePreserve',
            'GET' => 'Predis\Command\StringGet',
            'MGET' => 'Predis\Command\StringGetMultiple',
            'GETSET' => 'Predis\Command\StringGetSet',
            'INCR' => 'Predis\Command\StringIncrement',
            'INCRBY' => 'Predis\Command\StringIncrementBy',
            'DECR' => 'Predis\Command\StringDecrement',
            'DECRBY' => 'Predis\Command\StringDecrementBy',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\ListPushTail',
            'LPUSH' => 'Predis\Command\ListPushHead',
            'LLEN' => 'Predis\Command\ListLength',
            'LRANGE' => 'Predis\Command\ListRange',
            'LTRIM' => 'Predis\Command\ListTrim',
            'LINDEX' => 'Predis\Command\ListIndex',
            'LSET' => 'Predis\Command\ListSet',
            'LREM' => 'Predis\Command\ListRemove',
            'LPOP' => 'Predis\Command\ListPopFirst',
            'RPOP' => 'Predis\Command\ListPopLast',
            'RPOPLPUSH' => 'Predis\Command\ListPopLastPushHead',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\SetAdd',
            'SREM' => 'Predis\Command\SetRemove',
            'SPOP' => 'Predis\Command\SetPop',
            'SMOVE' => 'Predis\Command\SetMove',
            'SCARD' => 'Predis\Command\SetCardinality',
            'SISMEMBER' => 'Predis\Command\SetIsMember',
            'SINTER' => 'Predis\Command\SetIntersection',
            'SINTERSTORE' => 'Predis\Command\SetIntersectionStore',
            'SUNION' => 'Predis\Command\SetUnion',
            'SUNIONSTORE' => 'Predis\Command\SetUnionStore',
            'SDIFF' => 'Predis\Command\SetDifference',
            'SDIFFSTORE' => 'Predis\Command\SetDifferenceStore',
            'SMEMBERS' => 'Predis\Command\SetMembers',
            'SRANDMEMBER' => 'Predis\Command\SetRandomMember',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\ZSetAdd',
            'ZINCRBY' => 'Predis\Command\ZSetIncrementBy',
            'ZREM' => 'Predis\Command\ZSetRemove',
            'ZRANGE' => 'Predis\Command\ZSetRange',
            'ZREVRANGE' => 'Predis\Command\ZSetReverseRange',
            'ZRANGEBYSCORE' => 'Predis\Command\ZSetRangeByScore',
            'ZCARD' => 'Predis\Command\ZSetCardinality',
            'ZSCORE' => 'Predis\Command\ZSetScore',
            'ZREMRANGEBYSCORE' => 'Predis\Command\ZSetRemoveRangeByScore',

            /* connection related commands */
            'PING' => 'Predis\Command\ConnectionPing',
            'AUTH' => 'Predis\Command\ConnectionAuth',
            'SELECT' => 'Predis\Command\ConnectionSelect',
            'ECHO' => 'Predis\Command\ConnectionEcho',
            'QUIT' => 'Predis\Command\ConnectionQuit',

            /* remote server control commands */
            'INFO' => 'Predis\Command\ServerInfo',
            'SLAVEOF' => 'Predis\Command\ServerSlaveOf',
            'MONITOR' => 'Predis\Command\ServerMonitor',
            'DBSIZE' => 'Predis\Command\ServerDatabaseSize',
            'FLUSHDB' => 'Predis\Command\ServerFlushDatabase',
            'FLUSHALL' => 'Predis\Command\ServerFlushAll',
            'SAVE' => 'Predis\Command\ServerSave',
            'BGSAVE' => 'Predis\Command\ServerBackgroundSave',
            'LASTSAVE' => 'Predis\Command\ServerLastSave',
            'SHUTDOWN' => 'Predis\Command\ServerShutdown',
            'BGREWRITEAOF' => 'Predis\Command\ServerBackgroundRewriteAOF',

            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'SETEX' => 'Predis\Command\StringSetExpire',
            'APPEND' => 'Predis\Command\StringAppend',
            'SUBSTR' => 'Predis\Command\StringSubstr',

            /* commands operating on lists */
            'BLPOP' => 'Predis\Command\ListPopFirstBlocking',
            'BRPOP' => 'Predis\Command\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'ZUNIONSTORE' => 'Predis\Command\ZSetUnionStore',
            'ZINTERSTORE' => 'Predis\Command\ZSetIntersectionStore',
            'ZCOUNT' => 'Predis\Command\ZSetCount',
            'ZRANK' => 'Predis\Command\ZSetRank',
            'ZREVRANK' => 'Predis\Command\ZSetReverseRank',
            'ZREMRANGEBYRANK' => 'Predis\Command\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'HSET' => 'Predis\Command\HashSet',
            'HSETNX' => 'Predis\Command\HashSetPreserve',
            'HMSET' => 'Predis\Command\HashSetMultiple',
            'HINCRBY' => 'Predis\Command\HashIncrementBy',
            'HGET' => 'Predis\Command\HashGet',
            'HMGET' => 'Predis\Command\HashGetMultiple',
            'HDEL' => 'Predis\Command\HashDelete',
            'HEXISTS' => 'Predis\Command\HashExists',
            'HLEN' => 'Predis\Command\HashLength',
            'HKEYS' => 'Predis\Command\HashKeys',
            'HVALS' => 'Predis\Command\HashValues',
            'HGETALL' => 'Predis\Command\HashGetAll',

            /* transactions */
            'MULTI' => 'Predis\Command\TransactionMulti',
            'EXEC' => 'Predis\Command\TransactionExec',
            'DISCARD' => 'Predis\Command\TransactionDiscard',

            /* publish - subscribe */
            'SUBSCRIBE' => 'Predis\Command\PubSubSubscribe',
            'UNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribe',
            'PSUBSCRIBE' => 'Predis\Command\PubSubSubscribeByPattern',
            'PUNSUBSCRIBE' => 'Predis\Command\PubSubUnsubscribeByPattern',
            'PUBLISH' => 'Predis\Command\PubSubPublish',

            /* remote server control commands */
            'CONFIG' => 'Predis\Command\ServerConfig',

            /* ---------------- Redis 2.2 ---------------- */

            /* commands operating on the key space */
            'PERSIST' => 'Predis\Command\KeyPersist',

            /* commands operating on string values */
            'STRLEN' => 'Predis\Command\StringStrlen',
            'SETRANGE' => 'Predis\Command\StringSetRange',
            'GETRANGE' => 'Predis\Command\StringGetRange',
            'SETBIT' => 'Predis\Command\StringSetBit',
            'GETBIT' => 'Predis\Command\StringGetBit',

            /* commands operating on lists */
            'RPUSHX' => 'Predis\Command\ListPushTailX',
            'LPUSHX' => 'Predis\Command\ListPushHeadX',
            'LINSERT' => 'Predis\Command\ListInsert',
            'BRPOPLPUSH' => 'Predis\Command\ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'ZREVRANGEBYSCORE' => 'Predis\Command\ZSetReverseRangeByScore',

            /* transactions */
            'WATCH' => 'Predis\Command\TransactionWatch',
            'UNWATCH' => 'Predis\Command\TransactionUnwatch',

            /* remote server control commands */
            'OBJECT' => 'Predis\Command\ServerObject',
            'SLOWLOG' => 'Predis\Command\ServerSlowlog',
        );
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis;

use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Command\ScriptCommand;
use Predis\Configuration\Options;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\AggregateConnectionInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\ParametersInterface;
use Predis\Monitor\Consumer as MonitorConsumer;
use Predis\Pipeline\Pipeline;
use Predis\PubSub\Consumer as PubSubConsumer;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ResponseInterface;
use Predis\Response\ServerException;
use Predis\Transaction\MultiExec as MultiExecTransaction;
use Predis\Profile\ProfileInterface;
use Predis\Connection\NodeConnectionInterface;

/**
 * Base exception class for Predis-related errors.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class PredisException extends \Exception
{
}

/**
 * Interface defining a client-side context such as a pipeline or transaction.
 *
 * @method $this del(array $keys)
 * @method $this dump($key)
 * @method $this exists($key)
 * @method $this expire($key, $seconds)
 * @method $this expireat($key, $timestamp)
 * @method $this keys($pattern)
 * @method $this move($key, $db)
 * @method $this object($subcommand, $key)
 * @method $this persist($key)
 * @method $this pexpire($key, $milliseconds)
 * @method $this pexpireat($key, $timestamp)
 * @method $this pttl($key)
 * @method $this randomkey()
 * @method $this rename($key, $target)
 * @method $this renamenx($key, $target)
 * @method $this scan($cursor, array $options = null)
 * @method $this sort($key, array $options = null)
 * @method $this ttl($key)
 * @method $this type($key)
 * @method $this append($key, $value)
 * @method $this bitcount($key, $start = null, $end = null)
 * @method $this bitop($operation, $destkey, $key)
 * @method $this decr($key)
 * @method $this decrby($key, $decrement)
 * @method $this get($key)
 * @method $this getbit($key, $offset)
 * @method $this getrange($key, $start, $end)
 * @method $this getset($key, $value)
 * @method $this incr($key)
 * @method $this incrby($key, $increment)
 * @method $this incrbyfloat($key, $increment)
 * @method $this mget(array $keys)
 * @method $this mset(array $dictionary)
 * @method $this msetnx(array $dictionary)
 * @method $this psetex($key, $milliseconds, $value)
 * @method $this set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method $this setbit($key, $offset, $value)
 * @method $this setex($key, $seconds, $value)
 * @method $this setnx($key, $value)
 * @method $this setrange($key, $offset, $value)
 * @method $this strlen($key)
 * @method $this hdel($key, array $fields)
 * @method $this hexists($key, $field)
 * @method $this hget($key, $field)
 * @method $this hgetall($key)
 * @method $this hincrby($key, $field, $increment)
 * @method $this hincrbyfloat($key, $field, $increment)
 * @method $this hkeys($key)
 * @method $this hlen($key)
 * @method $this hmget($key, array $fields)
 * @method $this hmset($key, array $dictionary)
 * @method $this hscan($key, $cursor, array $options = null)
 * @method $this hset($key, $field, $value)
 * @method $this hsetnx($key, $field, $value)
 * @method $this hvals($key)
 * @method $this blpop(array $keys, $timeout)
 * @method $this brpop(array $keys, $timeout)
 * @method $this brpoplpush($source, $destination, $timeout)
 * @method $this lindex($key, $index)
 * @method $this linsert($key, $whence, $pivot, $value)
 * @method $this llen($key)
 * @method $this lpop($key)
 * @method $this lpush($key, array $values)
 * @method $this lpushx($key, $value)
 * @method $this lrange($key, $start, $stop)
 * @method $this lrem($key, $count, $value)
 * @method $this lset($key, $index, $value)
 * @method $this ltrim($key, $start, $stop)
 * @method $this rpop($key)
 * @method $this rpoplpush($source, $destination)
 * @method $this rpush($key, array $values)
 * @method $this rpushx($key, $value)
 * @method $this sadd($key, array $members)
 * @method $this scard($key)
 * @method $this sdiff(array $keys)
 * @method $this sdiffstore($destination, array $keys)
 * @method $this sinter(array $keys)
 * @method $this sinterstore($destination, array $keys)
 * @method $this sismember($key, $member)
 * @method $this smembers($key)
 * @method $this smove($source, $destination, $member)
 * @method $this spop($key)
 * @method $this srandmember($key, $count = null)
 * @method $this srem($key, $member)
 * @method $this sscan($key, $cursor, array $options = null)
 * @method $this sunion(array $keys)
 * @method $this sunionstore($destination, array $keys)
 * @method $this zadd($key, array $membersAndScoresDictionary)
 * @method $this zcard($key)
 * @method $this zcount($key, $min, $max)
 * @method $this zincrby($key, $increment, $member)
 * @method $this zinterstore($destination, array $keys, array $options = null)
 * @method $this zrange($key, $start, $stop, array $options = null)
 * @method $this zrangebyscore($key, $min, $max, array $options = null)
 * @method $this zrank($key, $member)
 * @method $this zrem($key, $member)
 * @method $this zremrangebyrank($key, $start, $stop)
 * @method $this zremrangebyscore($key, $min, $max)
 * @method $this zrevrange($key, $start, $stop, array $options = null)
 * @method $this zrevrangebyscore($key, $min, $max, array $options = null)
 * @method $this zrevrank($key, $member)
 * @method $this zunionstore($destination, array $keys, array $options = null)
 * @method $this zscore($key, $member)
 * @method $this zscan($key, $cursor, array $options = null)
 * @method $this zrangebylex($key, $start, $stop, array $options = null)
 * @method $this zremrangebylex($key, $min, $max)
 * @method $this zlexcount($key, $min, $max)
 * @method $this pfadd($key, array $elements)
 * @method $this pfmerge($destinationKey, array $sourceKeys)
 * @method $this pfcount(array $keys)
 * @method $this pubsub($subcommand, $argument)
 * @method $this publish($channel, $message)
 * @method $this discard()
 * @method $this exec()
 * @method $this multi()
 * @method $this unwatch()
 * @method $this watch($key)
 * @method $this eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method $this evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method $this script($subcommand, $argument = null)
 * @method $this auth($password)
 * @method $this echo($message)
 * @method $this ping($message = null)
 * @method $this select($database)
 * @method $this bgrewriteaof()
 * @method $this bgsave()
 * @method $this client($subcommand, $argument = null)
 * @method $this config($subcommand, $argument = null)
 * @method $this dbsize()
 * @method $this flushall()
 * @method $this flushdb()
 * @method $this info($section = null)
 * @method $this lastsave()
 * @method $this save()
 * @method $this slaveof($host, $port)
 * @method $this slowlog($subcommand, $argument = null)
 * @method $this time()
 * @method $this command()
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ClientContextInterface
{
    /**
     * Sends the specified command instance to Redis.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function executeCommand(CommandInterface $command);

    /**
     * Sends the specified command with its arguments to Redis.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return mixed
     */
    public function __call($method, $arguments);

    /**
     * Starts the execution of the context.
     *
     * @param mixed $callable Optional callback for execution.
     *
     * @return array
     */
    public function execute($callable = null);
}

/**
 * Base exception class for network-related errors.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class CommunicationException extends PredisException
{
    private $connection;

    /**
     * @param NodeConnectionInterface $connection     Connection that generated the exception.
     * @param string                  $message        Error message.
     * @param int                     $code           Error code.
     * @param \Exception              $innerException Inner exception for wrapping the original error.
     */
    public function __construct(
        NodeConnectionInterface $connection,
        $message = null,
        $code = null,
        \Exception $innerException = null
    ) {
        parent::__construct($message, $code, $innerException);
        $this->connection = $connection;
    }

    /**
     * Gets the connection that generated the exception.
     *
     * @return NodeConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Indicates if the receiver should reset the underlying connection.
     *
     * @return bool
     */
    public function shouldResetConnection()
    {
        return true;
    }

    /**
     * Helper method to handle exceptions generated by a connection object.
     *
     * @param CommunicationException $exception Exception.
     *
     * @throws CommunicationException
     */
    public static function handle(CommunicationException $exception)
    {
        if ($exception->shouldResetConnection()) {
            $connection = $exception->getConnection();

            if ($connection->isConnected()) {
                $connection->disconnect();
            }
        }

        throw $exception;
    }
}

/**
 * Interface defining a client able to execute commands against Redis.
 *
 * All the commands exposed by the client generally have the same signature as
 * described by the Redis documentation, but some of them offer an additional
 * and more friendly interface to ease programming which is described in the
 * following list of methods:
 *
 * @method int    del(array $keys)
 * @method string dump($key)
 * @method int    exists($key)
 * @method int    expire($key, $seconds)
 * @method int    expireat($key, $timestamp)
 * @method array  keys($pattern)
 * @method int    move($key, $db)
 * @method mixed  object($subcommand, $key)
 * @method int    persist($key)
 * @method int    pexpire($key, $milliseconds)
 * @method int    pexpireat($key, $timestamp)
 * @method int    pttl($key)
 * @method string randomkey()
 * @method mixed  rename($key, $target)
 * @method int    renamenx($key, $target)
 * @method array  scan($cursor, array $options = null)
 * @method array  sort($key, array $options = null)
 * @method int    ttl($key)
 * @method mixed  type($key)
 * @method int    append($key, $value)
 * @method int    bitcount($key, $start = null, $end = null)
 * @method int    bitop($operation, $destkey, $key)
 * @method int    decr($key)
 * @method int    decrby($key, $decrement)
 * @method string get($key)
 * @method int    getbit($key, $offset)
 * @method string getrange($key, $start, $end)
 * @method string getset($key, $value)
 * @method int    incr($key)
 * @method int    incrby($key, $increment)
 * @method string incrbyfloat($key, $increment)
 * @method array  mget(array $keys)
 * @method mixed  mset(array $dictionary)
 * @method int    msetnx(array $dictionary)
 * @method mixed  psetex($key, $milliseconds, $value)
 * @method mixed  set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method int    setbit($key, $offset, $value)
 * @method int    setex($key, $seconds, $value)
 * @method int    setnx($key, $value)
 * @method int    setrange($key, $offset, $value)
 * @method int    strlen($key)
 * @method int    hdel($key, array $fields)
 * @method int    hexists($key, $field)
 * @method string hget($key, $field)
 * @method array  hgetall($key)
 * @method int    hincrby($key, $field, $increment)
 * @method string hincrbyfloat($key, $field, $increment)
 * @method array  hkeys($key)
 * @method int    hlen($key)
 * @method array  hmget($key, array $fields)
 * @method mixed  hmset($key, array $dictionary)
 * @method array  hscan($key, $cursor, array $options = null)
 * @method int    hset($key, $field, $value)
 * @method int    hsetnx($key, $field, $value)
 * @method array  hvals($key)
 * @method array  blpop(array $keys, $timeout)
 * @method array  brpop(array $keys, $timeout)
 * @method array  brpoplpush($source, $destination, $timeout)
 * @method string lindex($key, $index)
 * @method int    linsert($key, $whence, $pivot, $value)
 * @method int    llen($key)
 * @method string lpop($key)
 * @method int    lpush($key, array $values)
 * @method int    lpushx($key, $value)
 * @method array  lrange($key, $start, $stop)
 * @method int    lrem($key, $count, $value)
 * @method mixed  lset($key, $index, $value)
 * @method mixed  ltrim($key, $start, $stop)
 * @method string rpop($key)
 * @method string rpoplpush($source, $destination)
 * @method int    rpush($key, array $values)
 * @method int    rpushx($key, $value)
 * @method int    sadd($key, array $members)
 * @method int    scard($key)
 * @method array  sdiff(array $keys)
 * @method int    sdiffstore($destination, array $keys)
 * @method array  sinter(array $keys)
 * @method int    sinterstore($destination, array $keys)
 * @method int    sismember($key, $member)
 * @method array  smembers($key)
 * @method int    smove($source, $destination, $member)
 * @method string spop($key)
 * @method string srandmember($key, $count = null)
 * @method int    srem($key, $member)
 * @method array  sscan($key, $cursor, array $options = null)
 * @method array  sunion(array $keys)
 * @method int    sunionstore($destination, array $keys)
 * @method int    zadd($key, array $membersAndScoresDictionary)
 * @method int    zcard($key)
 * @method string zcount($key, $min, $max)
 * @method string zincrby($key, $increment, $member)
 * @method int    zinterstore($destination, array $keys, array $options = null)
 * @method array  zrange($key, $start, $stop, array $options = null)
 * @method array  zrangebyscore($key, $min, $max, array $options = null)
 * @method int    zrank($key, $member)
 * @method int    zrem($key, $member)
 * @method int    zremrangebyrank($key, $start, $stop)
 * @method int    zremrangebyscore($key, $min, $max)
 * @method array  zrevrange($key, $start, $stop, array $options = null)
 * @method array  zrevrangebyscore($key, $min, $max, array $options = null)
 * @method int    zrevrank($key, $member)
 * @method int    zunionstore($destination, array $keys, array $options = null)
 * @method string zscore($key, $member)
 * @method array  zscan($key, $cursor, array $options = null)
 * @method array  zrangebylex($key, $start, $stop, array $options = null)
 * @method int    zremrangebylex($key, $min, $max)
 * @method int    zlexcount($key, $min, $max)
 * @method int    pfadd($key, array $elements)
 * @method mixed  pfmerge($destinationKey, array $sourceKeys)
 * @method int    pfcount(array $keys)
 * @method mixed  pubsub($subcommand, $argument)
 * @method int    publish($channel, $message)
 * @method mixed  discard()
 * @method array  exec()
 * @method mixed  multi()
 * @method mixed  unwatch()
 * @method mixed  watch($key)
 * @method mixed  eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed  evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed  script($subcommand, $argument = null)
 * @method mixed  auth($password)
 * @method string echo($message)
 * @method mixed  ping($message = null)
 * @method mixed  select($database)
 * @method mixed  bgrewriteaof()
 * @method mixed  bgsave()
 * @method mixed  client($subcommand, $argument = null)
 * @method mixed  config($subcommand, $argument = null)
 * @method int    dbsize()
 * @method mixed  flushall()
 * @method mixed  flushdb()
 * @method array  info($section = null)
 * @method int    lastsave()
 * @method mixed  save()
 * @method mixed  slaveof($host, $port)
 * @method mixed  slowlog($subcommand, $argument = null)
 * @method array  time()
 * @method array  command()
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ClientInterface
{
    /**
     * Returns the server profile used by the client.
     *
     * @return ProfileInterface
     */
    public function getProfile();

    /**
     * Returns the client options specified upon initialization.
     *
     * @return OptionsInterface
     */
    public function getOptions();

    /**
     * Opens the underlying connection to the server.
     */
    public function connect();

    /**
     * Closes the underlying connection from the server.
     */
    public function disconnect();

    /**
     * Returns the underlying connection instance.
     *
     * @return ConnectionInterface
     */
    public function getConnection();

    /**
     * Creates a new instance of the specified Redis command.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return CommandInterface
     */
    public function createCommand($method, $arguments = array());

    /**
     * Executes the specified Redis command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function executeCommand(CommandInterface $command);

    /**
     * Creates a Redis command with the specified arguments and sends a request
     * to the server.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return mixed
     */
    public function __call($method, $arguments);
}

/**
 * Exception class thrown when trying to use features not supported by certain
 * classes or abstractions of Predis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class NotSupportedException extends PredisException
{
}

/**
 * Exception class that identifies client-side errors.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ClientException extends PredisException
{
}

/**
 * Client class used for connecting and executing commands on Redis.
 *
 * This is the main high-level abstraction of Predis upon which various other
 * abstractions are built. Internally it aggregates various other classes each
 * one with its own responsibility and scope.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Client implements ClientInterface
{
    const VERSION = '1.0.3';

    protected $connection;
    protected $options;
    private $profile;

    /**
     * @param mixed $parameters Connection parameters for one or more servers.
     * @param mixed $options    Options to configure some behaviours of the client.
     */
    public function __construct($parameters = null, $options = null)
    {
        $this->options = $this->createOptions($options ?: array());
        $this->connection = $this->createConnection($parameters ?: array());
        $this->profile = $this->options->profile;
    }

    /**
     * Creates a new instance of Predis\Configuration\Options from different
     * types of arguments or simply returns the passed argument if it is an
     * instance of Predis\Configuration\OptionsInterface.
     *
     * @param mixed $options Client options.
     *
     * @throws \InvalidArgumentException
     *
     * @return OptionsInterface
     */
    protected function createOptions($options)
    {
        if (is_array($options)) {
            return new Options($options);
        }

        if ($options instanceof OptionsInterface) {
            return $options;
        }

        throw new \InvalidArgumentException('Invalid type for client options.');
    }

    /**
     * Creates single or aggregate connections from different types of arguments
     * (string, array) or returns the passed argument if it is an instance of a
     * class implementing Predis\Connection\ConnectionInterface.
     *
     * Accepted types for connection parameters are:
     *
     *  - Instance of Predis\Connection\ConnectionInterface.
     *  - Instance of Predis\Connection\ParametersInterface.
     *  - Array
     *  - String
     *  - Callable
     *
     * @param mixed $parameters Connection parameters or connection instance.
     *
     * @throws \InvalidArgumentException
     *
     * @return ConnectionInterface
     */
    protected function createConnection($parameters)
    {
        if ($parameters instanceof ConnectionInterface) {
            return $parameters;
        }

        if ($parameters instanceof ParametersInterface || is_string($parameters)) {
            return $this->options->connections->create($parameters);
        }

        if (is_array($parameters)) {
            if (!isset($parameters[0])) {
                return $this->options->connections->create($parameters);
            }

            $options = $this->options;

            if ($options->defined('aggregate')) {
                $initializer = $this->getConnectionInitializerWrapper($options->aggregate);
                $connection = $initializer($parameters, $options);
            } else {
                if ($options->defined('replication') && $replication = $options->replication) {
                    $connection = $replication;
                } else {
                    $connection = $options->cluster;
                }

                $options->connections->aggregate($connection, $parameters);
            }

            return $connection;
        }

        if (is_callable($parameters)) {
            $initializer = $this->getConnectionInitializerWrapper($parameters);
            $connection = $initializer($this->options);

            return $connection;
        }

        throw new \InvalidArgumentException('Invalid type for connection parameters.');
    }

    /**
     * Wraps a callable to make sure that its returned value represents a valid
     * connection type.
     *
     * @param mixed $callable
     *
     * @return \Closure
     */
    protected function getConnectionInitializerWrapper($callable)
    {
        return function () use ($callable) {
            $connection = call_user_func_array($callable, func_get_args());

            if (!$connection instanceof ConnectionInterface) {
                throw new \UnexpectedValueException(
                    'The callable connection initializer returned an invalid type.'
                );
            }

            return $connection;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Creates a new client instance for the specified connection ID or alias,
     * only when working with an aggregate connection (cluster, replication).
     * The new client instances uses the same options of the original one.
     *
     * @param string $connectionID Identifier of a connection.
     *
     * @throws \InvalidArgumentException
     *
     * @return Client
     */
    public function getClientFor($connectionID)
    {
        if (!$connection = $this->getConnectionById($connectionID)) {
            throw new \InvalidArgumentException("Invalid connection ID: $connectionID.");
        }

        return new static($connection, $this->options);
    }

    /**
     * Opens the underlying connection and connects to the server.
     */
    public function connect()
    {
        $this->connection->connect();
    }

    /**
     * Closes the underlying connection and disconnects from the server.
     */
    public function disconnect()
    {
        $this->connection->disconnect();
    }

    /**
     * Closes the underlying connection and disconnects from the server.
     *
     * This is the same as `Client::disconnect()` as it does not actually send
     * the `QUIT` command to Redis, but simply closes the connection.
     */
    public function quit()
    {
        $this->disconnect();
    }

    /**
     * Returns the current state of the underlying connection.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->connection->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Retrieves the specified connection from the aggregate connection when the
     * client is in cluster or replication mode.
     *
     * @param string $connectionID Index or alias of the single connection.
     *
     * @throws NotSupportedException
     *
     * @return Connection\NodeConnectionInterface
     */
    public function getConnectionById($connectionID)
    {
        if (!$this->connection instanceof AggregateConnectionInterface) {
            throw new NotSupportedException(
                'Retrieving connections by ID is supported only by aggregate connections.'
            );
        }

        return $this->connection->getConnectionById($connectionID);
    }

    /**
     * Executes a command without filtering its arguments, parsing the response,
     * applying any prefix to keys or throwing exceptions on Redis errors even
     * regardless of client options.
     *
     * It is possibile to indentify Redis error responses from normal responses
     * using the second optional argument which is populated by reference.
     *
     * @param array $arguments Command arguments as defined by the command signature.
     * @param bool  $error     Set to TRUE when Redis returned an error response.
     *
     * @return mixed
     */
    public function executeRaw(array $arguments, &$error = null)
    {
        $error = false;

        $response = $this->connection->executeCommand(
            new RawCommand($arguments)
        );

        if ($response instanceof ResponseInterface) {
            if ($response instanceof ErrorResponseInterface) {
                $error = true;
            }

            return (string) $response;
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($commandID, $arguments)
    {
        return $this->executeCommand(
            $this->createCommand($commandID, $arguments)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($commandID, $arguments = array())
    {
        return $this->profile->createCommand($commandID, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $response = $this->connection->executeCommand($command);

        if ($response instanceof ResponseInterface) {
            if ($response instanceof ErrorResponseInterface) {
                $response = $this->onErrorResponse($command, $response);
            }

            return $response;
        }

        return $command->parseResponse($response);
    }

    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface       $command  Redis command that generated the error.
     * @param ErrorResponseInterface $response Instance of the error response.
     *
     * @throws ServerException
     *
     * @return mixed
     */
    protected function onErrorResponse(CommandInterface $command, ErrorResponseInterface $response)
    {
        if ($command instanceof ScriptCommand && $response->getErrorType() === 'NOSCRIPT') {
            $eval = $this->createCommand('EVAL');
            $eval->setRawArguments($command->getEvalArguments());

            $response = $this->executeCommand($eval);

            if (!$response instanceof ResponseInterface) {
                $response = $command->parseResponse($response);
            }

            return $response;
        }

        if ($this->options->exceptions) {
            throw new ServerException($response->getMessage());
        }

        return $response;
    }

    /**
     * Executes the specified initializer method on `$this` by adjusting the
     * actual invokation depending on the arity (0, 1 or 2 arguments). This is
     * simply an utility method to create Redis contexts instances since they
     * follow a common initialization path.
     *
     * @param string $initializer Method name.
     * @param array  $argv        Arguments for the method.
     *
     * @return mixed
     */
    private function sharedContextFactory($initializer, $argv = null)
    {
        switch (count($argv)) {
            case 0:
                return $this->$initializer();

            case 1:
                return is_array($argv[0])
                    ? $this->$initializer($argv[0])
                    : $this->$initializer(null, $argv[0]);

            case 2:
                list($arg0, $arg1) = $argv;

                return $this->$initializer($arg0, $arg1);

            default:
                return $this->$initializer($this, $argv);
        }
    }

    /**
     * Creates a new pipeline context and returns it, or returns the results of
     * a pipeline executed inside the optionally provided callable object.
     *
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return Pipeline|array
     */
    public function pipeline(/* arguments */)
    {
        return $this->sharedContextFactory('createPipeline', func_get_args());
    }

    /**
     * Actual pipeline context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return Pipeline|array
     */
    protected function createPipeline(array $options = null, $callable = null)
    {
        if (isset($options['atomic']) && $options['atomic']) {
            $class = 'Predis\Pipeline\Atomic';
        } elseif (isset($options['fire-and-forget']) && $options['fire-and-forget']) {
            $class = 'Predis\Pipeline\FireAndForget';
        } else {
            $class = 'Predis\Pipeline\Pipeline';
        }

        /*
         * @var ClientContextInterface
         */
        $pipeline = new $class($this);

        if (isset($callable)) {
            return $pipeline->execute($callable);
        }

        return $pipeline;
    }

    /**
     * Creates a new transaction context and returns it, or returns the results
     * of a transaction executed inside the optionally provided callable object.
     *
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return MultiExecTransaction|array
     */
    public function transaction(/* arguments */)
    {
        return $this->sharedContextFactory('createTransaction', func_get_args());
    }

    /**
     * Actual transaction context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return MultiExecTransaction|array
     */
    protected function createTransaction(array $options = null, $callable = null)
    {
        $transaction = new MultiExecTransaction($this, $options);

        if (isset($callable)) {
            return $transaction->execute($callable);
        }

        return $transaction;
    }

    /**
     * Creates a new publis/subscribe context and returns it, or starts its loop
     * inside the optionally provided callable object.
     *
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return PubSubConsumer|null
     */
    public function pubSubLoop(/* arguments */)
    {
        return $this->sharedContextFactory('createPubSub', func_get_args());
    }

    /**
     * Actual publish/subscribe context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return PubSubConsumer|null
     */
    protected function createPubSub(array $options = null, $callable = null)
    {
        $pubsub = new PubSubConsumer($this, $options);

        if (!isset($callable)) {
            return $pubsub;
        }

        foreach ($pubsub as $message) {
            if (call_user_func($callable, $pubsub, $message) === false) {
                $pubsub->stop();
            }
        }
    }

    /**
     * Creates a new monitor consumer and returns it.
     *
     * @return MonitorConsumer
     */
    public function monitor()
    {
        return new MonitorConsumer($this);
    }
}

/**
 * Implements a lightweight PSR-0 compliant autoloader for Predis.
 *
 * @author Eric Naeseth <eric@thumbtack.com>
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Autoloader
{
    private $directory;
    private $prefix;
    private $prefixLength;

    /**
     * @param string $baseDirectory Base directory where the source files are located.
     */
    public function __construct($baseDirectory = __DIR__)
    {
        $this->directory = $baseDirectory;
        $this->prefix = __NAMESPACE__.'\\';
        $this->prefixLength = strlen($this->prefix);
    }

    /**
     * Registers the autoloader class with the PHP SPL autoloader.
     *
     * @param bool $prepend Prepend the autoloader on the stack instead of appending it.
     */
    public static function register($prepend = false)
    {
        spl_autoload_register(array(new self(), 'autoload'), true, $prepend);
    }

    /**
     * Loads a class from a file using its fully qualified name.
     *
     * @param string $className Fully qualified name of a class.
     */
    public function autoload($className)
    {
        if (0 === strpos($className, $this->prefix)) {
            $parts = explode('\\', substr($className, $this->prefixLength));
            $filepath = $this->directory.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts).'.php';

            if (is_file($filepath)) {
                require $filepath;
            }
        }
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Configuration;

use Predis\Connection\Aggregate\ClusterInterface;
use Predis\Connection\Aggregate\PredisCluster;
use Predis\Connection\Aggregate\RedisCluster;
use Predis\Connection\Factory;
use Predis\Connection\FactoryInterface;
use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Command\Processor\ProcessorInterface;
use Predis\Profile\Factory as Predis_Factory;
use Predis\Profile\ProfileInterface;
use Predis\Profile\RedisProfile;
use Predis\Connection\Aggregate\MasterSlaveReplication;
use Predis\Connection\Aggregate\ReplicationInterface;

/**
 * Defines an handler used by Predis\Configuration\Options to filter, validate
 * or return default values for a given option.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface OptionInterface
{
    /**
     * Filters and validates the passed value.
     *
     * @param OptionsInterface $options Options container.
     * @param mixed            $value   Input value.
     *
     * @return mixed
     */
    public function filter(OptionsInterface $options, $value);

    /**
     * Returns the default value for the option.
     *
     * @param OptionsInterface $options Options container.
     *
     * @return mixed
     */
    public function getDefault(OptionsInterface $options);
}

/**
 * Interface defining a container for client options.
 *
 * @property-read mixed aggregate   Custom connection aggregator.
 * @property-read mixed cluster     Aggregate connection for clustering.
 * @property-read mixed connections Connection factory.
 * @property-read mixed exceptions  Toggles exceptions in client for -ERR responses.
 * @property-read mixed prefix      Key prefixing strategy using the given prefix.
 * @property-read mixed profile     Server profile.
 * @property-read mixed replication Aggregate connection for replication.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface OptionsInterface
{
    /**
     * Returns the default value for the given option.
     *
     * @param string $option Name of the option.
     *
     * @return mixed|null
     */
    public function getDefault($option);

    /**
     * Checks if the given option has been set by the user upon initialization.
     *
     * @param string $option Name of the option.
     *
     * @return bool
     */
    public function defined($option);

    /**
     * Checks if the given option has been set and does not evaluate to NULL.
     *
     * @param string $option Name of the option.
     *
     * @return bool
     */
    public function __isset($option);

    /**
     * Returns the value of the given option.
     *
     * @param string $option Name of the option.
     *
     * @return mixed|null
     */
    public function __get($option);
}

/**
 * Configures a command processor that apply the specified prefix string to a
 * series of Redis commands considered prefixable.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PrefixOption implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if ($value instanceof ProcessorInterface) {
            return $value;
        }

        return new KeyPrefixProcessor($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        // NOOP
    }
}

/**
 * Configures the server profile to be used by the client to create command
 * instances depending on the specified version of the Redis server.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ProfileOption implements OptionInterface
{
    /**
     * Sets the commands processors that need to be applied to the profile.
     *
     * @param OptionsInterface $options Client options.
     * @param ProfileInterface $profile Server profile.
     */
    protected function setProcessors(OptionsInterface $options, ProfileInterface $profile)
    {
        if (isset($options->prefix) && $profile instanceof RedisProfile) {
            // NOTE: directly using __get('prefix') is actually a workaround for
            // HHVM 2.3.0. It's correct and respects the options interface, it's
            // just ugly. We will remove this hack when HHVM will fix re-entrant
            // calls to __get() once and for all.

            $profile->setProcessor($options->__get('prefix'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_string($value)) {
            $value = Predis_Factory::get($value);
            $this->setProcessors($options, $value);
        } elseif (!$value instanceof ProfileInterface) {
            throw new \InvalidArgumentException('Invalid value for the profile option.');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        $profile = Predis_Factory::getDefault();
        $this->setProcessors($options, $profile);

        return $profile;
    }
}

/**
 * Configures an aggregate connection used for master/slave replication among
 * multiple Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ReplicationOption implements OptionInterface
{
    /**
     * {@inheritdoc}
     *
     * @todo There's more code than needed due to a bug in filter_var() as
     *       discussed here https://bugs.php.net/bug.php?id=49510 and  different
     *       behaviours when encountering NULL values on PHP 5.3.
     */
    public function filter(OptionsInterface $options, $value)
    {
        if ($value instanceof ReplicationInterface) {
            return $value;
        }

        if (is_bool($value) || $value === null) {
            return $value ? $this->getDefault($options) : null;
        }

        if (
            !is_object($value) &&
            null !== $asbool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
        ) {
            return $asbool ? $this->getDefault($options) : null;
        }

        throw new \InvalidArgumentException(
            "An instance of type 'Predis\Connection\Aggregate\ReplicationInterface' was expected."
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return new MasterSlaveReplication();
    }
}

/**
 * Manages Predis options with filtering, conversion and lazy initialization of
 * values using a mini-DI container approach.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Options implements OptionsInterface
{
    protected $input;
    protected $options;
    protected $handlers;

    /**
     * @param array $options Array of options with their values
     */
    public function __construct(array $options = array())
    {
        $this->input = $options;
        $this->options = array();
        $this->handlers = $this->getHandlers();
    }

    /**
     * Ensures that the default options are initialized.
     *
     * @return array
     */
    protected function getHandlers()
    {
        return array(
            'cluster' => 'Predis\Configuration\ClusterOption',
            'connections' => 'Predis\Configuration\ConnectionFactoryOption',
            'exceptions' => 'Predis\Configuration\ExceptionsOption',
            'prefix' => 'Predis\Configuration\PrefixOption',
            'profile' => 'Predis\Configuration\ProfileOption',
            'replication' => 'Predis\Configuration\ReplicationOption',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault($option)
    {
        if (isset($this->handlers[$option])) {
            $handler = $this->handlers[$option];
            $handler = new $handler();

            return $handler->getDefault($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function defined($option)
    {
        return (
            array_key_exists($option, $this->options) ||
            array_key_exists($option, $this->input)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($option)
    {
        return (
            array_key_exists($option, $this->options) ||
            array_key_exists($option, $this->input)
        ) && $this->__get($option) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($option)
    {
        if (isset($this->options[$option]) || array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        if (isset($this->input[$option]) || array_key_exists($option, $this->input)) {
            $value = $this->input[$option];
            unset($this->input[$option]);

            if (is_object($value) && method_exists($value, '__invoke')) {
                $value = $value($this, $option);
            }

            if (isset($this->handlers[$option])) {
                $handler = $this->handlers[$option];
                $handler = new $handler();
                $value = $handler->filter($this, $value);
            }

            return $this->options[$option] = $value;
        }

        if (isset($this->handlers[$option])) {
            return $this->options[$option] = $this->getDefault($option);
        }

        return;
    }
}

/**
 * Configures a connection factory used by the client to create new connection
 * instances for single Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionFactoryOption implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if ($value instanceof FactoryInterface) {
            return $value;
        } elseif (is_array($value)) {
            $factory = $this->getDefault($options);

            foreach ($value as $scheme => $initializer) {
                $factory->define($scheme, $initializer);
            }

            return $factory;
        } else {
            throw new \InvalidArgumentException(
                'Invalid value provided for the connections option.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return new Factory();
    }
}

/**
 * Configures whether consumers (such as the client) should throw exceptions on
 * Redis errors (-ERR responses) or just return instances of error responses.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ExceptionsOption implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return true;
    }
}

/**
 * Configures an aggregate connection used for clustering
 * multiple Redis nodes using various implementations with
 * different algorithms or strategies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ClusterOption implements OptionInterface
{
    /**
     * Creates a new cluster connection from on a known descriptive name.
     *
     * @param OptionsInterface $options Instance of the client options.
     * @param string           $id      Descriptive identifier of the cluster type (`predis`, `redis-cluster`)
     *
     * @return ClusterInterface|null
     */
    protected function createByDescription(OptionsInterface $options, $id)
    {
        switch ($id) {
            case 'predis':
            case 'predis-cluster':
                return new PredisCluster();

            case 'redis':
            case 'redis-cluster':
                return new RedisCluster($options->connections);

            default:
                return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_string($value)) {
            $value = $this->createByDescription($options, $value);
        }

        if (!$value instanceof ClusterInterface) {
            throw new \InvalidArgumentException(
                "An instance of type 'Predis\Connection\Aggregate\ClusterInterface' was expected."
            );
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return new PredisCluster();
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Response;

use Predis\PredisException;

/**
 * Represents a complex response object from Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ResponseInterface
{
}

/**
 * Represents an error returned by Redis (responses identified by "-" in the
 * Redis protocol) during the execution of an operation on the server.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ErrorInterface extends ResponseInterface
{
    /**
     * Returns the error message.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Returns the error type (e.g. ERR, ASK, MOVED).
     *
     * @return string
     */
    public function getErrorType();
}

/**
 * Represents a status response returned by Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Status implements ResponseInterface
{
    private static $OK;
    private static $QUEUED;

    private $payload;

    /**
     * @param string $payload Payload of the status response as returned by Redis.
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Converts the response object to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->payload;
    }

    /**
     * Returns the payload of status response.
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Returns an instance of a status response object.
     *
     * Common status responses such as OK or QUEUED are cached in order to lower
     * the global memory usage especially when using pipelines.
     *
     * @param string $payload Status response payload.
     *
     * @return string
     */
    public static function get($payload)
    {
        switch ($payload) {
            case 'OK':
            case 'QUEUED':
                if (isset(self::$$payload)) {
                    return self::$$payload;
                }

                return self::$$payload = new self($payload);

            default:
                return new self($payload);
        }
    }
}

/**
 * Represents an error returned by Redis (-ERR responses) during the execution
 * of a command on the server.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Error implements ErrorInterface
{
    private $message;

    /**
     * @param string $message Error message returned by Redis
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorType()
    {
        list($errorType) = explode(' ', $this->getMessage(), 2);

        return $errorType;
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getMessage();
    }
}

/**
 * Exception class that identifies server-side Redis errors.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerException extends PredisException implements ErrorInterface
{
    /**
     * Gets the type of the error returned by Redis.
     *
     * @return string
     */
    public function getErrorType()
    {
        list($errorType) = explode(' ', $this->getMessage(), 2);

        return $errorType;
    }

    /**
     * Converts the exception to an instance of Predis\Response\Error.
     *
     * @return Error
     */
    public function toErrorResponse()
    {
        return new Error($this->getMessage());
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Protocol\Text\Handler;

use Predis\CommunicationException;
use Predis\Connection\CompositeConnectionInterface;
use Predis\Protocol\ProtocolException;
use Predis\Response\Error;
use Predis\Response\Status;
use Predis\Response\Iterator\MultiBulk as MultiBulkIterator;

/**
 * Defines a pluggable handler used to parse a particular type of response.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ResponseHandlerInterface
{
    /**
     * Deserializes a response returned by Redis and reads more data from the
     * connection if needed.
     *
     * @param CompositeConnectionInterface $connection Redis connection.
     * @param string                       $payload    String payload.
     *
     * @return mixed
     */
    public function handle(CompositeConnectionInterface $connection, $payload);
}

/**
 * Handler for the status response type in the standard Redis wire protocol. It
 * translates certain classes of status response to PHP objects or just returns
 * the payload as a string.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StatusResponse implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(CompositeConnectionInterface $connection, $payload)
    {
        return Status::get($payload);
    }
}

/**
 * Handler for the multibulk response type in the standard Redis wire protocol.
 * It returns multibulk responses as iterators that can stream bulk elements.
 *
 * Streamable multibulk responses are not globally supported by the abstractions
 * built-in into Predis, such as transactions or pipelines. Use them with care!
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StreamableMultiBulkResponse implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(CompositeConnectionInterface $connection, $payload)
    {
        $length = (int) $payload;

        if ("$length" != $payload) {
            CommunicationException::handle(new ProtocolException(
                $connection, "Cannot parse '$payload' as a valid length for a multi-bulk response."
            ));
        }

        return new MultiBulkIterator($connection, $length);
    }
}

/**
 * Handler for the multibulk response type in the standard Redis wire protocol.
 * It returns multibulk responses as PHP arrays.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiBulkResponse implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(CompositeConnectionInterface $connection, $payload)
    {
        $length = (int) $payload;

        if ("$length" !== $payload) {
            CommunicationException::handle(new ProtocolException(
                $connection, "Cannot parse '$payload' as a valid length of a multi-bulk response."
            ));
        }

        if ($length === -1) {
            return;
        }

        $list = array();

        if ($length > 0) {
            $handlersCache = array();
            $reader = $connection->getProtocol()->getResponseReader();

            for ($i = 0; $i < $length; ++$i) {
                $header = $connection->readLine();
                $prefix = $header[0];

                if (isset($handlersCache[$prefix])) {
                    $handler = $handlersCache[$prefix];
                } else {
                    $handler = $reader->getHandler($prefix);
                    $handlersCache[$prefix] = $handler;
                }

                $list[$i] = $handler->handle($connection, substr($header, 1));
            }
        }

        return $list;
    }
}

/**
 * Handler for the error response type in the standard Redis wire protocol.
 * It translates the payload to a complex response object for Predis.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ErrorResponse implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(CompositeConnectionInterface $connection, $payload)
    {
        return new Error($payload);
    }
}

/**
 * Handler for the integer response type in the standard Redis wire protocol.
 * It translates the payload an integer or NULL.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class IntegerResponse implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(CompositeConnectionInterface $connection, $payload)
    {
        if (is_numeric($payload)) {
            return (int) $payload;
        }

        if ($payload !== 'nil') {
            CommunicationException::handle(new ProtocolException(
                $connection, "Cannot parse '$payload' as a valid numeric response."
            ));
        }

        return;
    }
}

/**
 * Handler for the bulk response type in the standard Redis wire protocol.
 * It translates the payload to a string or a NULL.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class BulkResponse implements ResponseHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(CompositeConnectionInterface $connection, $payload)
    {
        $length = (int) $payload;

        if ("$length" !== $payload) {
            CommunicationException::handle(new ProtocolException(
                $connection, "Cannot parse '$payload' as a valid length for a bulk response."
            ));
        }

        if ($length >= 0) {
            return substr($connection->readBuffer($length + 2), 0, -2);
        }

        if ($length == -1) {
            return;
        }

        CommunicationException::handle(new ProtocolException(
            $connection, "Value '$payload' is not a valid length for a bulk response."
        ));

        return;
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Collection\Iterator;

use Predis\ClientInterface;
use Predis\NotSupportedException;

/**
 * Provides the base implementation for a fully-rewindable PHP iterator that can
 * incrementally iterate over cursor-based collections stored on Redis using the
 * commands in the `SCAN` family.
 *
 * Given their incremental nature with multiple fetches, these kind of iterators
 * offer limited guarantees about the returned elements because the collection
 * can change several times during the iteration process.
 *
 * @see http://redis.io/commands/scan
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class CursorBasedIterator implements \Iterator
{
    protected $client;
    protected $match;
    protected $count;

    protected $valid;
    protected $fetchmore;
    protected $elements;
    protected $cursor;
    protected $position;
    protected $current;

    /**
     * @param ClientInterface $client Client connected to Redis.
     * @param string          $match  Pattern to match during the server-side iteration.
     * @param int             $count  Hint used by Redis to compute the number of results per iteration.
     */
    public function __construct(ClientInterface $client, $match = null, $count = null)
    {
        $this->client = $client;
        $this->match = $match;
        $this->count = $count;

        $this->reset();
    }

    /**
     * Ensures that the client supports the specified Redis command required to
     * fetch elements from the server to perform the iteration.
     *
     * @param ClientInterface $client    Client connected to Redis.
     * @param string          $commandID Command ID.
     *
     * @throws NotSupportedException
     */
    protected function requiredCommand(ClientInterface $client, $commandID)
    {
        if (!$client->getProfile()->supportsCommand($commandID)) {
            throw new NotSupportedException("The current profile does not support '$commandID'.");
        }
    }

    /**
     * Resets the inner state of the iterator.
     */
    protected function reset()
    {
        $this->valid = true;
        $this->fetchmore = true;
        $this->elements = array();
        $this->cursor = 0;
        $this->position = -1;
        $this->current = null;
    }

    /**
     * Returns an array of options for the `SCAN` command.
     *
     * @return array
     */
    protected function getScanOptions()
    {
        $options = array();

        if (strlen($this->match) > 0) {
            $options['MATCH'] = $this->match;
        }

        if ($this->count > 0) {
            $options['COUNT'] = $this->count;
        }

        return $options;
    }

    /**
     * Fetches a new set of elements from the remote collection, effectively
     * advancing the iteration process.
     *
     * @return array
     */
    abstract protected function executeCommand();

    /**
     * Populates the local buffer of elements fetched from the server during
     * the iteration.
     */
    protected function fetch()
    {
        list($cursor, $elements) = $this->executeCommand();

        if (!$cursor) {
            $this->fetchmore = false;
        }

        $this->cursor = $cursor;
        $this->elements = $elements;
    }

    /**
     * Extracts next values for key() and current().
     */
    protected function extractNext()
    {
        ++$this->position;
        $this->current = array_shift($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->reset();
        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        tryFetch: {
            if (!$this->elements && $this->fetchmore) {
                $this->fetch();
            }

            if ($this->elements) {
                $this->extractNext();
            } elseif ($this->cursor) {
                goto tryFetch;
            } else {
                $this->valid = false;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->valid;
    }
}

/**
 * Abstracts the iteration of members stored in a sorted set by leveraging the
 * ZSCAN command (Redis >= 2.8) wrapped in a fully-rewindable PHP iterator.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 *
 * @link http://redis.io/commands/scan
 */
class SortedSetKey extends CursorBasedIterator
{
    protected $key;

    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client, $key, $match = null, $count = null)
    {
        $this->requiredCommand($client, 'ZSCAN');

        parent::__construct($client, $match, $count);

        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        return $this->client->zscan($this->key, $this->cursor, $this->getScanOptions());
    }

    /**
     * {@inheritdoc}
     */
    protected function extractNext()
    {
        if ($kv = each($this->elements)) {
            $this->position = $kv[0];
            $this->current = $kv[1];

            unset($this->elements[$this->position]);
        }
    }
}

/**
 * Abstracts the iteration of members stored in a set by leveraging the SSCAN
 * command (Redis >= 2.8) wrapped in a fully-rewindable PHP iterator.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 *
 * @link http://redis.io/commands/scan
 */
class SetKey extends CursorBasedIterator
{
    protected $key;

    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client, $key, $match = null, $count = null)
    {
        $this->requiredCommand($client, 'SSCAN');

        parent::__construct($client, $match, $count);

        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        return $this->client->sscan($this->key, $this->cursor, $this->getScanOptions());
    }
}

/**
 * Abstracts the iteration of the keyspace on a Redis instance by leveraging the
 * SCAN command (Redis >= 2.8) wrapped in a fully-rewindable PHP iterator.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 *
 * @link http://redis.io/commands/scan
 */
class Keyspace extends CursorBasedIterator
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client, $match = null, $count = null)
    {
        $this->requiredCommand($client, 'SCAN');

        parent::__construct($client, $match, $count);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        return $this->client->scan($this->cursor, $this->getScanOptions());
    }
}

/**
 * Abstracts the iteration of fields and values of an hash by leveraging the
 * HSCAN command (Redis >= 2.8) wrapped in a fully-rewindable PHP iterator.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 *
 * @link http://redis.io/commands/scan
 */
class HashKey extends CursorBasedIterator
{
    protected $key;

    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client, $key, $match = null, $count = null)
    {
        $this->requiredCommand($client, 'HSCAN');

        parent::__construct($client, $match, $count);

        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        return $this->client->hscan($this->key, $this->cursor, $this->getScanOptions());
    }

    /**
     * {@inheritdoc}
     */
    protected function extractNext()
    {
        $this->position = key($this->elements);
        $this->current = array_shift($this->elements);
    }
}

/**
 * Abstracts the iteration of items stored in a list by leveraging the LRANGE
 * command wrapped in a fully-rewindable PHP iterator.
 *
 * This iterator tries to emulate the behaviour of cursor-based iterators based
 * on the SCAN-family of commands introduced in Redis <= 2.8, meaning that due
 * to its incremental nature with multiple fetches it can only offer limited
 * guarantees on the returned elements because the collection can change several
 * times (trimmed, deleted, overwritten) during the iteration process.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 *
 * @link http://redis.io/commands/lrange
 */
class ListKey implements \Iterator
{
    protected $client;
    protected $count;
    protected $key;

    protected $valid;
    protected $fetchmore;
    protected $elements;
    protected $position;
    protected $current;

    /**
     * @param ClientInterface $client Client connected to Redis.
     * @param string          $key    Redis list key.
     * @param int             $count  Number of items retrieved on each fetch operation.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(ClientInterface $client, $key, $count = 10)
    {
        $this->requiredCommand($client, 'LRANGE');

        if ((false === $count = filter_var($count, FILTER_VALIDATE_INT)) || $count < 0) {
            throw new \InvalidArgumentException('The $count argument must be a positive integer.');
        }

        $this->client = $client;
        $this->key = $key;
        $this->count = $count;

        $this->reset();
    }

    /**
     * Ensures that the client instance supports the specified Redis command
     * required to fetch elements from the server to perform the iteration.
     *
     * @param ClientInterface $client    Client connected to Redis.
     * @param string          $commandID Command ID.
     *
     * @throws NotSupportedException
     */
    protected function requiredCommand(ClientInterface $client, $commandID)
    {
        if (!$client->getProfile()->supportsCommand($commandID)) {
            throw new NotSupportedException("The current profile does not support '$commandID'.");
        }
    }

    /**
     * Resets the inner state of the iterator.
     */
    protected function reset()
    {
        $this->valid = true;
        $this->fetchmore = true;
        $this->elements = array();
        $this->position = -1;
        $this->current = null;
    }

    /**
     * Fetches a new set of elements from the remote collection, effectively
     * advancing the iteration process.
     *
     * @return array
     */
    protected function executeCommand()
    {
        return $this->client->lrange($this->key, $this->position + 1, $this->position + $this->count);
    }

    /**
     * Populates the local buffer of elements fetched from the server during the
     * iteration.
     */
    protected function fetch()
    {
        $elements = $this->executeCommand();

        if (count($elements) < $this->count) {
            $this->fetchmore = false;
        }

        $this->elements = $elements;
    }

    /**
     * Extracts next values for key() and current().
     */
    protected function extractNext()
    {
        ++$this->position;
        $this->current = array_shift($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->reset();
        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if (!$this->elements && $this->fetchmore) {
            $this->fetch();
        }

        if ($this->elements) {
            $this->extractNext();
        } else {
            $this->valid = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->valid;
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Cluster;

use Predis\Command\CommandInterface;
use Predis\Command\ScriptCommand;
use Predis\Cluster\Distributor\DistributorInterface;
use Predis\Cluster\Distributor\HashRing;
use Predis\Cluster\Hash\CRC16;
use Predis\Cluster\Hash\HashGeneratorInterface;
use Predis\NotSupportedException;

/**
 * Interface for classes defining the strategy used to calculate an hash out of
 * keys extracted from supported commands.
 *
 * This is mostly useful to support clustering via client-side sharding.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface StrategyInterface
{
    /**
     * Returns a slot for the given command used for clustering distribution or
     * NULL when this is not possible.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return int
     */
    public function getSlot(CommandInterface $command);

    /**
     * Returns a slot for the given key used for clustering distribution or NULL
     * when this is not possible.
     *
     * @param string $key Key string.
     *
     * @return int
     */
    public function getSlotByKey($key);

    /**
     * Returns a distributor instance to be used by the cluster.
     *
     * @return DistributorInterface
     */
    public function getDistributor();
}

/**
 * Common class implementing the logic needed to support clustering strategies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class ClusterStrategy implements StrategyInterface
{
    protected $commands;

    /**
     *
     */
    public function __construct()
    {
        $this->commands = $this->getDefaultCommands();
    }

    /**
     * Returns the default map of supported commands with their handlers.
     *
     * @return array
     */
    protected function getDefaultCommands()
    {
        $getKeyFromFirstArgument = array($this, 'getKeyFromFirstArgument');
        $getKeyFromAllArguments = array($this, 'getKeyFromAllArguments');

        return array(
            /* commands operating on the key space */
            'EXISTS' => $getKeyFromFirstArgument,
            'DEL' => $getKeyFromAllArguments,
            'TYPE' => $getKeyFromFirstArgument,
            'EXPIRE' => $getKeyFromFirstArgument,
            'EXPIREAT' => $getKeyFromFirstArgument,
            'PERSIST' => $getKeyFromFirstArgument,
            'PEXPIRE' => $getKeyFromFirstArgument,
            'PEXPIREAT' => $getKeyFromFirstArgument,
            'TTL' => $getKeyFromFirstArgument,
            'PTTL' => $getKeyFromFirstArgument,
            'SORT' => $getKeyFromFirstArgument, // TODO
            'DUMP' => $getKeyFromFirstArgument,
            'RESTORE' => $getKeyFromFirstArgument,

            /* commands operating on string values */
            'APPEND' => $getKeyFromFirstArgument,
            'DECR' => $getKeyFromFirstArgument,
            'DECRBY' => $getKeyFromFirstArgument,
            'GET' => $getKeyFromFirstArgument,
            'GETBIT' => $getKeyFromFirstArgument,
            'MGET' => $getKeyFromAllArguments,
            'SET' => $getKeyFromFirstArgument,
            'GETRANGE' => $getKeyFromFirstArgument,
            'GETSET' => $getKeyFromFirstArgument,
            'INCR' => $getKeyFromFirstArgument,
            'INCRBY' => $getKeyFromFirstArgument,
            'INCRBYFLOAT' => $getKeyFromFirstArgument,
            'SETBIT' => $getKeyFromFirstArgument,
            'SETEX' => $getKeyFromFirstArgument,
            'MSET' => array($this, 'getKeyFromInterleavedArguments'),
            'MSETNX' => array($this, 'getKeyFromInterleavedArguments'),
            'SETNX' => $getKeyFromFirstArgument,
            'SETRANGE' => $getKeyFromFirstArgument,
            'STRLEN' => $getKeyFromFirstArgument,
            'SUBSTR' => $getKeyFromFirstArgument,
            'BITOP' => array($this, 'getKeyFromBitOp'),
            'BITCOUNT' => $getKeyFromFirstArgument,

            /* commands operating on lists */
            'LINSERT' => $getKeyFromFirstArgument,
            'LINDEX' => $getKeyFromFirstArgument,
            'LLEN' => $getKeyFromFirstArgument,
            'LPOP' => $getKeyFromFirstArgument,
            'RPOP' => $getKeyFromFirstArgument,
            'RPOPLPUSH' => $getKeyFromAllArguments,
            'BLPOP' => array($this, 'getKeyFromBlockingListCommands'),
            'BRPOP' => array($this, 'getKeyFromBlockingListCommands'),
            'BRPOPLPUSH' => array($this, 'getKeyFromBlockingListCommands'),
            'LPUSH' => $getKeyFromFirstArgument,
            'LPUSHX' => $getKeyFromFirstArgument,
            'RPUSH' => $getKeyFromFirstArgument,
            'RPUSHX' => $getKeyFromFirstArgument,
            'LRANGE' => $getKeyFromFirstArgument,
            'LREM' => $getKeyFromFirstArgument,
            'LSET' => $getKeyFromFirstArgument,
            'LTRIM' => $getKeyFromFirstArgument,

            /* commands operating on sets */
            'SADD' => $getKeyFromFirstArgument,
            'SCARD' => $getKeyFromFirstArgument,
            'SDIFF' => $getKeyFromAllArguments,
            'SDIFFSTORE' => $getKeyFromAllArguments,
            'SINTER' => $getKeyFromAllArguments,
            'SINTERSTORE' => $getKeyFromAllArguments,
            'SUNION' => $getKeyFromAllArguments,
            'SUNIONSTORE' => $getKeyFromAllArguments,
            'SISMEMBER' => $getKeyFromFirstArgument,
            'SMEMBERS' => $getKeyFromFirstArgument,
            'SSCAN' => $getKeyFromFirstArgument,
            'SPOP' => $getKeyFromFirstArgument,
            'SRANDMEMBER' => $getKeyFromFirstArgument,
            'SREM' => $getKeyFromFirstArgument,

            /* commands operating on sorted sets */
            'ZADD' => $getKeyFromFirstArgument,
            'ZCARD' => $getKeyFromFirstArgument,
            'ZCOUNT' => $getKeyFromFirstArgument,
            'ZINCRBY' => $getKeyFromFirstArgument,
            'ZINTERSTORE' => array($this, 'getKeyFromZsetAggregationCommands'),
            'ZRANGE' => $getKeyFromFirstArgument,
            'ZRANGEBYSCORE' => $getKeyFromFirstArgument,
            'ZRANK' => $getKeyFromFirstArgument,
            'ZREM' => $getKeyFromFirstArgument,
            'ZREMRANGEBYRANK' => $getKeyFromFirstArgument,
            'ZREMRANGEBYSCORE' => $getKeyFromFirstArgument,
            'ZREVRANGE' => $getKeyFromFirstArgument,
            'ZREVRANGEBYSCORE' => $getKeyFromFirstArgument,
            'ZREVRANK' => $getKeyFromFirstArgument,
            'ZSCORE' => $getKeyFromFirstArgument,
            'ZUNIONSTORE' => array($this, 'getKeyFromZsetAggregationCommands'),
            'ZSCAN' => $getKeyFromFirstArgument,
            'ZLEXCOUNT' => $getKeyFromFirstArgument,
            'ZRANGEBYLEX' => $getKeyFromFirstArgument,
            'ZREMRANGEBYLEX' => $getKeyFromFirstArgument,
            'ZREVRANGEBYLEX' => $getKeyFromFirstArgument,

            /* commands operating on hashes */
            'HDEL' => $getKeyFromFirstArgument,
            'HEXISTS' => $getKeyFromFirstArgument,
            'HGET' => $getKeyFromFirstArgument,
            'HGETALL' => $getKeyFromFirstArgument,
            'HMGET' => $getKeyFromFirstArgument,
            'HMSET' => $getKeyFromFirstArgument,
            'HINCRBY' => $getKeyFromFirstArgument,
            'HINCRBYFLOAT' => $getKeyFromFirstArgument,
            'HKEYS' => $getKeyFromFirstArgument,
            'HLEN' => $getKeyFromFirstArgument,
            'HSET' => $getKeyFromFirstArgument,
            'HSETNX' => $getKeyFromFirstArgument,
            'HVALS' => $getKeyFromFirstArgument,
            'HSCAN' => $getKeyFromFirstArgument,
            'HSTRLEN' => $getKeyFromFirstArgument,

            /* commands operating on HyperLogLog */
            'PFADD' => $getKeyFromFirstArgument,
            'PFCOUNT' => $getKeyFromAllArguments,
            'PFMERGE' => $getKeyFromAllArguments,

            /* scripting */
            'EVAL' => array($this, 'getKeyFromScriptingCommands'),
            'EVALSHA' => array($this, 'getKeyFromScriptingCommands'),
        );
    }

    /**
     * Returns the list of IDs for the supported commands.
     *
     * @return array
     */
    public function getSupportedCommands()
    {
        return array_keys($this->commands);
    }

    /**
     * Sets an handler for the specified command ID.
     *
     * The signature of the callback must have a single parameter of type
     * Predis\Command\CommandInterface.
     *
     * When the callback argument is omitted or NULL, the previously associated
     * handler for the specified command ID is removed.
     *
     * @param string $commandID Command ID.
     * @param mixed  $callback  A valid callable object, or NULL to unset the handler.
     *
     * @throws \InvalidArgumentException
     */
    public function setCommandHandler($commandID, $callback = null)
    {
        $commandID = strtoupper($commandID);

        if (!isset($callback)) {
            unset($this->commands[$commandID]);

            return;
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(
                'The argument must be a callable object or NULL.'
            );
        }

        $this->commands[$commandID] = $callback;
    }

    /**
     * Extracts the key from the first argument of a command instance.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string
     */
    protected function getKeyFromFirstArgument(CommandInterface $command)
    {
        return $command->getArgument(0);
    }

    /**
     * Extracts the key from a command with multiple keys only when all keys in
     * the arguments array produce the same hash.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function getKeyFromAllArguments(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if ($this->checkSameSlotForKeys($arguments)) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from a command with multiple keys only when all keys in
     * the arguments array produce the same hash.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function getKeyFromInterleavedArguments(CommandInterface $command)
    {
        $arguments = $command->getArguments();
        $keys = array();

        for ($i = 0; $i < count($arguments); $i += 2) {
            $keys[] = $arguments[$i];
        }

        if ($this->checkSameSlotForKeys($keys)) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from BLPOP and BRPOP commands.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function getKeyFromBlockingListCommands(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if ($this->checkSameSlotForKeys(array_slice($arguments, 0, count($arguments) - 1))) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from BITOP command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function getKeyFromBitOp(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if ($this->checkSameSlotForKeys(array_slice($arguments, 1, count($arguments)))) {
            return $arguments[1];
        }
    }

    /**
     * Extracts the key from ZINTERSTORE and ZUNIONSTORE commands.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function getKeyFromZsetAggregationCommands(CommandInterface $command)
    {
        $arguments = $command->getArguments();
        $keys = array_merge(array($arguments[0]), array_slice($arguments, 2, $arguments[1]));

        if ($this->checkSameSlotForKeys($keys)) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from EVAL and EVALSHA commands.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return string|null
     */
    protected function getKeyFromScriptingCommands(CommandInterface $command)
    {
        if ($command instanceof ScriptCommand) {
            $keys = $command->getKeys();
        } else {
            $keys = array_slice($args = $command->getArguments(), 2, $args[1]);
        }

        if ($keys && $this->checkSameSlotForKeys($keys)) {
            return $keys[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSlot(CommandInterface $command)
    {
        $slot = $command->getSlot();

        if (!isset($slot) && isset($this->commands[$cmdID = $command->getId()])) {
            $key = call_user_func($this->commands[$cmdID], $command);

            if (isset($key)) {
                $slot = $this->getSlotByKey($key);
                $command->setSlot($slot);
            }
        }

        return $slot;
    }

    /**
     * Checks if the specified array of keys will generate the same hash.
     *
     * @param array $keys Array of keys.
     *
     * @return bool
     */
    protected function checkSameSlotForKeys(array $keys)
    {
        if (!$count = count($keys)) {
            return false;
        }

        $currentSlot = $this->getSlotByKey($keys[0]);

        for ($i = 1; $i < $count; ++$i) {
            $nextSlot = $this->getSlotByKey($keys[$i]);

            if ($currentSlot !== $nextSlot) {
                return false;
            }

            $currentSlot = $nextSlot;
        }

        return true;
    }

    /**
     * Returns only the hashable part of a key (delimited by "{...}"), or the
     * whole key if a key tag is not found in the string.
     *
     * @param string $key A key.
     *
     * @return string
     */
    protected function extractKeyTag($key)
    {
        if (false !== $start = strpos($key, '{')) {
            if (false !== ($end = strpos($key, '}', $start)) && $end !== ++$start) {
                $key = substr($key, $start, $end - $start);
            }
        }

        return $key;
    }
}

/**
 * Default cluster strategy used by Predis to handle client-side sharding.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PredisStrategy extends ClusterStrategy
{
    protected $distributor;

    /**
     * @param DistributorInterface $distributor Optional distributor instance.
     */
    public function __construct(DistributorInterface $distributor = null)
    {
        parent::__construct();

        $this->distributor = $distributor ?: new HashRing();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlotByKey($key)
    {
        $key = $this->extractKeyTag($key);
        $hash = $this->distributor->hash($key);
        $slot = $this->distributor->getSlot($hash);

        return $slot;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkSameSlotForKeys(array $keys)
    {
        if (!$count = count($keys)) {
            return false;
        }

        $currentKey = $this->extractKeyTag($keys[0]);

        for ($i = 1; $i < $count; ++$i) {
            $nextKey = $this->extractKeyTag($keys[$i]);

            if ($currentKey !== $nextKey) {
                return false;
            }

            $currentKey = $nextKey;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDistributor()
    {
        return $this->distributor;
    }
}

/**
 * Default class used by Predis to calculate hashes out of keys of
 * commands supported by redis-cluster.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisStrategy extends ClusterStrategy
{
    protected $hashGenerator;

    /**
     * @param HashGeneratorInterface $hashGenerator Hash generator instance.
     */
    public function __construct(HashGeneratorInterface $hashGenerator = null)
    {
        parent::__construct();

        $this->hashGenerator = $hashGenerator ?: new CRC16();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlotByKey($key)
    {
        $key = $this->extractKeyTag($key);
        $slot = $this->hashGenerator->hash($key) & 0x3FFF;

        return $slot;
    }

    /**
     * {@inheritdoc}
     */
    public function getDistributor()
    {
        throw new NotSupportedException(
            'This cluster strategy does not provide an external distributor'
        );
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Protocol;

use Predis\CommunicationException;
use Predis\Command\CommandInterface;
use Predis\Connection\CompositeConnectionInterface;

/**
 * Defines a pluggable protocol processor capable of serializing commands and
 * deserializing responses into PHP objects directly from a connection.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ProtocolProcessorInterface
{
    /**
     * Writes a request over a connection to Redis.
     *
     * @param CompositeConnectionInterface $connection Redis connection.
     * @param CommandInterface             $command    Command instance.
     */
    public function write(CompositeConnectionInterface $connection, CommandInterface $command);

    /**
     * Reads a response from a connection to Redis.
     *
     * @param CompositeConnectionInterface $connection Redis connection.
     *
     * @return mixed
     */
    public function read(CompositeConnectionInterface $connection);
}

/**
 * Defines a pluggable reader capable of parsing responses returned by Redis and
 * deserializing them to PHP objects.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ResponseReaderInterface
{
    /**
     * Reads a response from a connection to Redis.
     *
     * @param CompositeConnectionInterface $connection Redis connection.
     *
     * @return mixed
     */
    public function read(CompositeConnectionInterface $connection);
}

/**
 * Defines a pluggable serializer for Redis commands.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface RequestSerializerInterface
{
    /**
     * Serializes a Redis command.
     *
     * @param CommandInterface $command Redis command.
     *
     * @return string
     */
    public function serialize(CommandInterface $command);
}

/**
 * Exception used to indentify errors encountered while parsing the Redis wire
 * protocol.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ProtocolException extends CommunicationException
{
}

/* --------------------------------------------------------------------------- */

namespace Predis\Connection\Aggregate;

use Predis\Connection\AggregateConnectionInterface;
use Predis\Command\CommandInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Replication\ReplicationStrategy;
use Predis\Cluster\PredisStrategy;
use Predis\Cluster\StrategyInterface;
use Predis\NotSupportedException;
use Predis\Cluster\RedisStrategy as RedisClusterStrategy;
use Predis\Command\RawCommand;
use Predis\Connection\FactoryInterface;
use Predis\Response\ErrorInterface as ErrorResponseInterface;

/**
 * Defines a cluster of Redis servers formed by aggregating multiple connection
 * instances to single Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ClusterInterface extends AggregateConnectionInterface
{
}

/**
 * Defines a group of Redis nodes in a master / slave replication setup.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ReplicationInterface extends AggregateConnectionInterface
{
    /**
     * Switches the internal connection instance in use.
     *
     * @param string $connection Alias of a connection
     */
    public function switchTo($connection);

    /**
     * Returns the connection instance currently in use by the aggregate
     * connection.
     *
     * @return NodeConnectionInterface
     */
    public function getCurrent();

    /**
     * Returns the connection instance for the master Redis node.
     *
     * @return NodeConnectionInterface
     */
    public function getMaster();

    /**
     * Returns a list of connection instances to slave nodes.
     *
     * @return NodeConnectionInterface
     */
    public function getSlaves();
}

/**
 * Abstraction for a Redis-backed cluster of nodes (Redis >= 3.0.0).
 *
 * This connection backend offers smart support for redis-cluster by handling
 * automatic slots map (re)generation upon -MOVED or -ASK responses returned by
 * Redis when redirecting a client to a different node.
 *
 * The cluster can be pre-initialized using only a subset of the actual nodes in
 * the cluster, Predis will do the rest by adjusting the slots map and creating
 * the missing underlying connection instances on the fly.
 *
 * It is possible to pre-associate connections to a slots range with the "slots"
 * parameter in the form "$first-$last". This can greatly reduce runtime node
 * guessing and redirections.
 *
 * It is also possible to ask for the full and updated slots map directly to one
 * of the nodes and optionally enable such a behaviour upon -MOVED redirections.
 * Asking for the cluster configuration to Redis is actually done by issuing a
 * CLUSTER SLOTS command to a random node in the pool.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisCluster implements ClusterInterface, \IteratorAggregate, \Countable
{
    private $useClusterSlots = true;
    private $defaultParameters = array();
    private $pool = array();
    private $slots = array();
    private $slotsMap;
    private $strategy;
    private $connections;

    /**
     * @param FactoryInterface  $connections Optional connection factory.
     * @param StrategyInterface $strategy    Optional cluster strategy.
     */
    public function __construct(
        FactoryInterface $connections,
        StrategyInterface $strategy = null
    ) {
        $this->connections = $connections;
        $this->strategy = $strategy ?: new RedisClusterStrategy();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        foreach ($this->pool as $connection) {
            if ($connection->isConnected()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if ($connection = $this->getRandomConnection()) {
            $connection->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        foreach ($this->pool as $connection) {
            $connection->disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(NodeConnectionInterface $connection)
    {
        $this->pool[(string) $connection] = $connection;
        unset($this->slotsMap);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeConnectionInterface $connection)
    {
        if (false !== $id = array_search($connection, $this->pool, true)) {
            unset(
                $this->pool[$id],
                $this->slotsMap
            );

            return true;
        }

        return false;
    }

    /**
     * Removes a connection instance by using its identifier.
     *
     * @param string $connectionID Connection identifier.
     *
     * @return bool True if the connection was in the pool.
     */
    public function removeById($connectionID)
    {
        if (isset($this->pool[$connectionID])) {
            unset(
                $this->pool[$connectionID],
                $this->slotsMap
            );

            return true;
        }

        return false;
    }

    /**
     * Generates the current slots map by guessing the cluster configuration out
     * of the connection parameters of the connections in the pool.
     *
     * Generation is based on the same algorithm used by Redis to generate the
     * cluster, so it is most effective when all of the connections supplied on
     * initialization have the "slots" parameter properly set accordingly to the
     * current cluster configuration.
     */
    public function buildSlotsMap()
    {
        $this->slotsMap = array();

        foreach ($this->pool as $connectionID => $connection) {
            $parameters = $connection->getParameters();

            if (!isset($parameters->slots)) {
                continue;
            }

            $slots = explode('-', $parameters->slots, 2);
            $this->setSlots($slots[0], $slots[1], $connectionID);
        }
    }

    /**
     * Generates an updated slots map fetching the cluster configuration using
     * the CLUSTER SLOTS command against the specified node or a random one from
     * the pool.
     *
     * @param NodeConnectionInterface $connection Optional connection instance.
     *
     * @return array
     */
    public function askSlotsMap(NodeConnectionInterface $connection = null)
    {
        if (!$connection && !$connection = $this->getRandomConnection()) {
            return array();
        }

        $command = RawCommand::create('CLUSTER', 'SLOTS');
        $response = $connection->executeCommand($command);

        foreach ($response as $slots) {
            // We only support master servers for now, so we ignore subsequent
            // elements in the $slots array identifying slaves.
            list($start, $end, $master) = $slots;

            if ($master[0] === '') {
                $this->setSlots($start, $end, (string) $connection);
            } else {
                $this->setSlots($start, $end, "{$master[0]}:{$master[1]}");
            }
        }

        return $this->slotsMap;
    }

    /**
     * Returns the current slots map for the cluster.
     *
     * @return array
     */
    public function getSlotsMap()
    {
        if (!isset($this->slotsMap)) {
            $this->slotsMap = array();
        }

        return $this->slotsMap;
    }

    /**
     * Pre-associates a connection to a slots range to avoid runtime guessing.
     *
     * @param int                            $first      Initial slot of the range.
     * @param int                            $last       Last slot of the range.
     * @param NodeConnectionInterface|string $connection ID or connection instance.
     *
     * @throws \OutOfBoundsException
     */
    public function setSlots($first, $last, $connection)
    {
        if ($first < 0x0000 || $first > 0x3FFF ||
            $last < 0x0000 || $last > 0x3FFF ||
            $last < $first
        ) {
            throw new \OutOfBoundsException(
                "Invalid slot range for $connection: [$first-$last]."
            );
        }

        $slots = array_fill($first, $last - $first + 1, (string) $connection);
        $this->slotsMap = $this->getSlotsMap() + $slots;
    }

    /**
     * Guesses the correct node associated to a given slot using a precalculated
     * slots map, falling back to the same logic used by Redis to initialize a
     * cluster (best-effort).
     *
     * @param int $slot Slot index.
     *
     * @return string Connection ID.
     */
    protected function guessNode($slot)
    {
        if (!isset($this->slotsMap)) {
            $this->buildSlotsMap();
        }

        if (isset($this->slotsMap[$slot])) {
            return $this->slotsMap[$slot];
        }

        $count = count($this->pool);
        $index = min((int) ($slot / (int) (16384 / $count)), $count - 1);
        $nodes = array_keys($this->pool);

        return $nodes[$index];
    }

    /**
     * Creates a new connection instance from the given connection ID.
     *
     * @param string $connectionID Identifier for the connection.
     *
     * @return NodeConnectionInterface
     */
    protected function createConnection($connectionID)
    {
        $separator = strrpos($connectionID, ':');

        $parameters = array_merge($this->defaultParameters, array(
            'host' => substr($connectionID, 0, $separator),
            'port' => substr($connectionID, $separator + 1),
        ));

        $connection = $this->connections->create($parameters);

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(CommandInterface $command)
    {
        $slot = $this->strategy->getSlot($command);

        if (!isset($slot)) {
            throw new NotSupportedException(
                "Cannot use '{$command->getId()}' with redis-cluster."
            );
        }

        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        } else {
            return $this->getConnectionBySlot($slot);
        }
    }

    /**
     * Returns the connection currently associated to a given slot.
     *
     * @param int $slot Slot index.
     *
     * @throws \OutOfBoundsException
     *
     * @return NodeConnectionInterface
     */
    public function getConnectionBySlot($slot)
    {
        if ($slot < 0x0000 || $slot > 0x3FFF) {
            throw new \OutOfBoundsException("Invalid slot [$slot].");
        }

        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        }

        $connectionID = $this->guessNode($slot);

        if (!$connection = $this->getConnectionById($connectionID)) {
            $connection = $this->createConnection($connectionID);
            $this->pool[$connectionID] = $connection;
        }

        return $this->slots[$slot] = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionID)
    {
        if (isset($this->pool[$connectionID])) {
            return $this->pool[$connectionID];
        }
    }

    /**
     * Returns a random connection from the pool.
     *
     * @return NodeConnectionInterface|null
     */
    protected function getRandomConnection()
    {
        if ($this->pool) {
            return $this->pool[array_rand($this->pool)];
        }
    }

    /**
     * Permanently associates the connection instance to a new slot.
     * The connection is added to the connections pool if not yet included.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     * @param int                     $slot       Target slot index.
     */
    protected function move(NodeConnectionInterface $connection, $slot)
    {
        $this->pool[(string) $connection] = $connection;
        $this->slots[(int) $slot] = $connection;
    }

    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface       $command Command that generated the -ERR response.
     * @param ErrorResponseInterface $error   Redis error response object.
     *
     * @return mixed
     */
    protected function onErrorResponse(CommandInterface $command, ErrorResponseInterface $error)
    {
        $details = explode(' ', $error->getMessage(), 2);

        switch ($details[0]) {
            case 'MOVED':
                return $this->onMovedResponse($command, $details[1]);

            case 'ASK':
                return $this->onAskResponse($command, $details[1]);

            default:
                return $error;
        }
    }

    /**
     * Handles -MOVED responses by executing again the command against the node
     * indicated by the Redis response.
     *
     * @param CommandInterface $command Command that generated the -MOVED response.
     * @param string           $details Parameters of the -MOVED response.
     *
     * @return mixed
     */
    protected function onMovedResponse(CommandInterface $command, $details)
    {
        list($slot, $connectionID) = explode(' ', $details, 2);

        if (!$connection = $this->getConnectionById($connectionID)) {
            $connection = $this->createConnection($connectionID);
        }

        if ($this->useClusterSlots) {
            $this->askSlotsMap($connection);
        }

        $this->move($connection, $slot);
        $response = $this->executeCommand($command);

        return $response;
    }

    /**
     * Handles -ASK responses by executing again the command against the node
     * indicated by the Redis response.
     *
     * @param CommandInterface $command Command that generated the -ASK response.
     * @param string           $details Parameters of the -ASK response.
     *
     * @return mixed
     */
    protected function onAskResponse(CommandInterface $command, $details)
    {
        list($slot, $connectionID) = explode(' ', $details, 2);

        if (!$connection = $this->getConnectionById($connectionID)) {
            $connection = $this->createConnection($connectionID);
        }

        $connection->executeCommand(RawCommand::create('ASKING'));
        $response = $connection->executeCommand($command);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->getConnection($command)->writeRequest($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->getConnection($command)->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $connection = $this->getConnection($command);
        $response = $connection->executeCommand($command);

        if ($response instanceof ErrorResponseInterface) {
            return $this->onErrorResponse($command, $response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->pool);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->pool));
    }

    /**
     * Returns the underlying command hash strategy used to hash commands by
     * using keys found in their arguments.
     *
     * @return StrategyInterface
     */
    public function getClusterStrategy()
    {
        return $this->strategy;
    }

    /**
     * Returns the underlying connection factory used to create new connection
     * instances to Redis nodes indicated by redis-cluster.
     *
     * @return FactoryInterface
     */
    public function getConnectionFactory()
    {
        return $this->connections;
    }

    /**
     * Enables automatic fetching of the current slots map from one of the nodes
     * using the CLUSTER SLOTS command. This option is disabled by default but
     * asking the current slots map to Redis upon -MOVED responses may reduce
     * overhead by eliminating the trial-and-error nature of the node guessing
     * procedure, mostly when targeting many keys that would end up in a lot of
     * redirections.
     *
     * The slots map can still be manually fetched using the askSlotsMap()
     * method whether or not this option is enabled.
     *
     * @param bool $value Enable or disable the use of CLUSTER SLOTS.
     */
    public function useClusterSlots($value)
    {
        $this->useClusterSlots = (bool) $value;
    }

    /**
     * Sets a default array of connection parameters to be applied when creating
     * new connection instances on the fly when they are not part of the initial
     * pool supplied upon cluster initialization.
     *
     * These parameters are not applied to connections added to the pool using
     * the add() method.
     *
     * @param array $parameters Array of connection parameters.
     */
    public function setDefaultParameters(array $parameters)
    {
        $this->defaultParameters = array_merge(
            $this->defaultParameters,
            $parameters ?: array()
        );
    }
}

/**
 * Abstraction for a cluster of aggregate connections to various Redis servers
 * implementing client-side sharding based on pluggable distribution strategies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 *
 * @todo Add the ability to remove connections from pool.
 */
class PredisCluster implements ClusterInterface, \IteratorAggregate, \Countable
{
    private $pool;
    private $strategy;
    private $distributor;

    /**
     * @param StrategyInterface $strategy Optional cluster strategy.
     */
    public function __construct(StrategyInterface $strategy = null)
    {
        $this->pool = array();
        $this->strategy = $strategy ?: new PredisStrategy();
        $this->distributor = $this->strategy->getDistributor();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        foreach ($this->pool as $connection) {
            if ($connection->isConnected()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        foreach ($this->pool as $connection) {
            $connection->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        foreach ($this->pool as $connection) {
            $connection->disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(NodeConnectionInterface $connection)
    {
        $parameters = $connection->getParameters();

        if (isset($parameters->alias)) {
            $this->pool[$parameters->alias] = $connection;
        } else {
            $this->pool[] = $connection;
        }

        $weight = isset($parameters->weight) ? $parameters->weight : null;
        $this->distributor->add($connection, $weight);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeConnectionInterface $connection)
    {
        if (($id = array_search($connection, $this->pool, true)) !== false) {
            unset($this->pool[$id]);
            $this->distributor->remove($connection);

            return true;
        }

        return false;
    }

    /**
     * Removes a connection instance using its alias or index.
     *
     * @param string $connectionID Alias or index of a connection.
     *
     * @return bool Returns true if the connection was in the pool.
     */
    public function removeById($connectionID)
    {
        if ($connection = $this->getConnectionById($connectionID)) {
            return $this->remove($connection);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(CommandInterface $command)
    {
        $slot = $this->strategy->getSlot($command);

        if (!isset($slot)) {
            throw new NotSupportedException(
                "Cannot use '{$command->getId()}' over clusters of connections."
            );
        }

        $node = $this->distributor->getBySlot($slot);

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionID)
    {
        return isset($this->pool[$connectionID]) ? $this->pool[$connectionID] : null;
    }

    /**
     * Retrieves a connection instance from the cluster using a key.
     *
     * @param string $key Key string.
     *
     * @return NodeConnectionInterface
     */
    public function getConnectionByKey($key)
    {
        $hash = $this->strategy->getSlotByKey($key);
        $node = $this->distributor->getBySlot($hash);

        return $node;
    }

    /**
     * Returns the underlying command hash strategy used to hash commands by
     * using keys found in their arguments.
     *
     * @return StrategyInterface
     */
    public function getClusterStrategy()
    {
        return $this->strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->pool);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->pool);
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->getConnection($command)->writeRequest($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->getConnection($command)->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->getConnection($command)->executeCommand($command);
    }

    /**
     * Executes the specified Redis command on all the nodes of a cluster.
     *
     * @param CommandInterface $command A Redis command.
     *
     * @return array
     */
    public function executeCommandOnNodes(CommandInterface $command)
    {
        $responses = array();

        foreach ($this->pool as $connection) {
            $responses[] = $connection->executeCommand($command);
        }

        return $responses;
    }
}

/**
 * Aggregate connection handling replication of Redis nodes configured in a
 * single master / multiple slaves setup.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MasterSlaveReplication implements ReplicationInterface
{
    protected $strategy;
    protected $master;
    protected $slaves;
    protected $current;

    /**
     * {@inheritdoc}
     */
    public function __construct(ReplicationStrategy $strategy = null)
    {
        $this->slaves = array();
        $this->strategy = $strategy ?: new ReplicationStrategy();
    }

    /**
     * Checks if one master and at least one slave have been defined.
     */
    protected function check()
    {
        if (!isset($this->master) || !$this->slaves) {
            throw new \RuntimeException('Replication needs one master and at least one slave.');
        }
    }

    /**
     * Resets the connection state.
     */
    protected function reset()
    {
        $this->current = null;
    }

    /**
     * {@inheritdoc}
     */
    public function add(NodeConnectionInterface $connection)
    {
        $alias = $connection->getParameters()->alias;

        if ($alias === 'master') {
            $this->master = $connection;
        } else {
            $this->slaves[$alias ?: count($this->slaves)] = $connection;
        }

        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeConnectionInterface $connection)
    {
        if ($connection->getParameters()->alias === 'master') {
            $this->master = null;
            $this->reset();

            return true;
        } else {
            if (($id = array_search($connection, $this->slaves, true)) !== false) {
                unset($this->slaves[$id]);
                $this->reset();

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(CommandInterface $command)
    {
        if ($this->current === null) {
            $this->check();
            $this->current = $this->strategy->isReadOperation($command)
                ? $this->pickSlave()
                : $this->master;

            return $this->current;
        }

        if ($this->current === $this->master) {
            return $this->current;
        }

        if (!$this->strategy->isReadOperation($command)) {
            $this->current = $this->master;
        }

        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionId)
    {
        if ($connectionId === 'master') {
            return $this->master;
        }

        if (isset($this->slaves[$connectionId])) {
            return $this->slaves[$connectionId];
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function switchTo($connection)
    {
        $this->check();

        if (!$connection instanceof NodeConnectionInterface) {
            $connection = $this->getConnectionById($connection);
        }
        if ($connection !== $this->master && !in_array($connection, $this->slaves, true)) {
            throw new \InvalidArgumentException('Invalid connection or connection not found.');
        }

        $this->current = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlaves()
    {
        return array_values($this->slaves);
    }

    /**
     * Returns the underlying replication strategy.
     *
     * @return ReplicationStrategy
     */
    public function getReplicationStrategy()
    {
        return $this->strategy;
    }

    /**
     * Returns a random slave.
     *
     * @return NodeConnectionInterface
     */
    protected function pickSlave()
    {
        return $this->slaves[array_rand($this->slaves)];
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->current ? $this->current->isConnected() : false;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if ($this->current === null) {
            $this->check();
            $this->current = $this->pickSlave();
        }

        $this->current->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->master) {
            $this->master->disconnect();
        }

        foreach ($this->slaves as $connection) {
            $connection->disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->getConnection($command)->writeRequest($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->getConnection($command)->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->getConnection($command)->executeCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array('master', 'slaves', 'strategy');
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Pipeline;

use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ResponseInterface;
use Predis\Response\ServerException;
use Predis\CommunicationException;
use Predis\Connection\Aggregate\ClusterInterface;
use Predis\NotSupportedException;
use Predis\ClientContextInterface;
use Predis\Command\CommandInterface;
use Predis\Connection\Aggregate\ReplicationInterface;

/**
 * Implementation of a command pipeline in which write and read operations of
 * Redis commands are pipelined to alleviate the effects of network round-trips.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Pipeline implements ClientContextInterface
{
    private $client;
    private $pipeline;

    private $responses = array();
    private $running = false;

    /**
     * @param ClientInterface $client Client instance used by the context.
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
        $this->pipeline = new \SplQueue();
    }

    /**
     * Queues a command into the pipeline buffer.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return $this
     */
    public function __call($method, $arguments)
    {
        $command = $this->client->createCommand($method, $arguments);
        $this->recordCommand($command);

        return $this;
    }

    /**
     * Queues a command instance into the pipeline buffer.
     *
     * @param CommandInterface $command Command to be queued in the buffer.
     */
    protected function recordCommand(CommandInterface $command)
    {
        $this->pipeline->enqueue($command);
    }

    /**
     * Queues a command instance into the pipeline buffer.
     *
     * @param CommandInterface $command Command instance to be queued in the buffer.
     *
     * @return $this
     */
    public function executeCommand(CommandInterface $command)
    {
        $this->recordCommand($command);

        return $this;
    }

    /**
     * Throws an exception on -ERR responses returned by Redis.
     *
     * @param ConnectionInterface    $connection Redis connection that returned the error.
     * @param ErrorResponseInterface $response   Instance of the error response.
     *
     * @throws ServerException
     */
    protected function exception(ConnectionInterface $connection, ErrorResponseInterface $response)
    {
        $connection->disconnect();
        $message = $response->getMessage();

        throw new ServerException($message);
    }

    /**
     * Returns the underlying connection to be used by the pipeline.
     *
     * @return ConnectionInterface
     */
    protected function getConnection()
    {
        $connection = $this->getClient()->getConnection();

        if ($connection instanceof ReplicationInterface) {
            $connection->switchTo('master');
        }

        return $connection;
    }

    /**
     * Implements the logic to flush the queued commands and read the responses
     * from the current connection.
     *
     * @param ConnectionInterface $connection Current connection instance.
     * @param \SplQueue           $commands   Queued commands.
     *
     * @return array
     */
    protected function executePipeline(ConnectionInterface $connection, \SplQueue $commands)
    {
        foreach ($commands as $command) {
            $connection->writeRequest($command);
        }

        $responses = array();
        $exceptions = $this->throwServerExceptions();

        while (!$commands->isEmpty()) {
            $command = $commands->dequeue();
            $response = $connection->readResponse($command);

            if (!$response instanceof ResponseInterface) {
                $responses[] = $command->parseResponse($response);
            } elseif ($response instanceof ErrorResponseInterface && $exceptions) {
                $this->exception($connection, $response);
            } else {
                $responses[] = $response;
            }
        }

        return $responses;
    }

    /**
     * Flushes the buffer holding all of the commands queued so far.
     *
     * @param bool $send Specifies if the commands in the buffer should be sent to Redis.
     *
     * @return $this
     */
    public function flushPipeline($send = true)
    {
        if ($send && !$this->pipeline->isEmpty()) {
            $responses = $this->executePipeline($this->getConnection(), $this->pipeline);
            $this->responses = array_merge($this->responses, $responses);
        } else {
            $this->pipeline = new \SplQueue();
        }

        return $this;
    }

    /**
     * Marks the running status of the pipeline.
     *
     * @param bool $bool Sets the running status of the pipeline.
     *
     * @throws ClientException
     */
    private function setRunning($bool)
    {
        if ($bool && $this->running) {
            throw new ClientException('The current pipeline context is already being executed.');
        }

        $this->running = $bool;
    }

    /**
     * Handles the actual execution of the whole pipeline.
     *
     * @param mixed $callable Optional callback for execution.
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function execute($callable = null)
    {
        if ($callable && !is_callable($callable)) {
            throw new \InvalidArgumentException('The argument must be a callable object.');
        }

        $exception = null;
        $this->setRunning(true);

        try {
            if ($callable) {
                call_user_func($callable, $this);
            }

            $this->flushPipeline();
        } catch (\Exception $exception) {
            // NOOP
        }

        $this->setRunning(false);

        if ($exception) {
            throw $exception;
        }

        return $this->responses;
    }

    /**
     * Returns if the pipeline should throw exceptions on server errors.
     *
     * @return bool
     */
    protected function throwServerExceptions()
    {
        return (bool) $this->client->getOptions()->exceptions;
    }

    /**
     * Returns the underlying client instance used by the pipeline object.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}

/**
 * Command pipeline that writes commands to the servers but discards responses.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class FireAndForget extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, \SplQueue $commands)
    {
        while (!$commands->isEmpty()) {
            $connection->writeRequest($commands->dequeue());
        }

        $connection->disconnect();

        return array();
    }
}

/**
 * Command pipeline that does not throw exceptions on connection errors, but
 * returns the exception instances as the rest of the response elements.
 *
 * @todo Awful naming!
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ConnectionErrorProof extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    protected function getConnection()
    {
        return $this->getClient()->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, \SplQueue $commands)
    {
        if ($connection instanceof NodeConnectionInterface) {
            return $this->executeSingleNode($connection, $commands);
        } elseif ($connection instanceof ClusterInterface) {
            return $this->executeCluster($connection, $commands);
        } else {
            $class = get_class($connection);

            throw new NotSupportedException("The connection class '$class' is not supported.");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function executeSingleNode(NodeConnectionInterface $connection, \SplQueue $commands)
    {
        $responses = array();
        $sizeOfPipe = count($commands);

        foreach ($commands as $command) {
            try {
                $connection->writeRequest($command);
            } catch (CommunicationException $exception) {
                return array_fill(0, $sizeOfPipe, $exception);
            }
        }

        for ($i = 0; $i < $sizeOfPipe; ++$i) {
            $command = $commands->dequeue();

            try {
                $responses[$i] = $connection->readResponse($command);
            } catch (CommunicationException $exception) {
                $add = count($commands) - count($responses);
                $responses = array_merge($responses, array_fill(0, $add, $exception));

                break;
            }
        }

        return $responses;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCluster(ClusterInterface $connection, \SplQueue $commands)
    {
        $responses = array();
        $sizeOfPipe = count($commands);
        $exceptions = array();

        foreach ($commands as $command) {
            $cmdConnection = $connection->getConnection($command);

            if (isset($exceptions[spl_object_hash($cmdConnection)])) {
                continue;
            }

            try {
                $cmdConnection->writeRequest($command);
            } catch (CommunicationException $exception) {
                $exceptions[spl_object_hash($cmdConnection)] = $exception;
            }
        }

        for ($i = 0; $i < $sizeOfPipe; ++$i) {
            $command = $commands->dequeue();

            $cmdConnection = $connection->getConnection($command);
            $connectionHash = spl_object_hash($cmdConnection);

            if (isset($exceptions[$connectionHash])) {
                $responses[$i] = $exceptions[$connectionHash];
                continue;
            }

            try {
                $responses[$i] = $cmdConnection->readResponse($command);
            } catch (CommunicationException $exception) {
                $responses[$i] = $exception;
                $exceptions[$connectionHash] = $exception;
            }
        }

        return $responses;
    }
}

/**
 * Command pipeline wrapped into a MULTI / EXEC transaction.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Atomic extends Pipeline
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client)
    {
        if (!$client->getProfile()->supportsCommands(array('multi', 'exec', 'discard'))) {
            throw new ClientException(
                "The current profile does not support 'MULTI', 'EXEC' and 'DISCARD'."
            );
        }

        parent::__construct($client);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnection()
    {
        $connection = $this->getClient()->getConnection();

        if (!$connection instanceof NodeConnectionInterface) {
            $class = __CLASS__;

            throw new ClientException("The class '$class' does not support aggregate connections.");
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function executePipeline(ConnectionInterface $connection, \SplQueue $commands)
    {
        $profile = $this->getClient()->getProfile();
        $connection->executeCommand($profile->createCommand('multi'));

        foreach ($commands as $command) {
            $connection->writeRequest($command);
        }

        foreach ($commands as $command) {
            $response = $connection->readResponse($command);

            if ($response instanceof ErrorResponseInterface) {
                $connection->executeCommand($profile->createCommand('discard'));
                throw new ServerException($response->getMessage());
            }
        }

        $executed = $connection->executeCommand($profile->createCommand('exec'));

        if (!isset($executed)) {
            // TODO: should be throwing a more appropriate exception.
            throw new ClientException(
                'The underlying transaction has been aborted by the server.'
            );
        }

        if (count($executed) !== count($commands)) {
            $expected = count($commands);
            $received = count($executed);

            throw new ClientException(
                "Invalid number of responses [expected $expected, received $received]."
            );
        }

        $responses = array();
        $sizeOfPipe = count($commands);
        $exceptions = $this->throwServerExceptions();

        for ($i = 0; $i < $sizeOfPipe; ++$i) {
            $command = $commands->dequeue();
            $response = $executed[$i];

            if (!$response instanceof ResponseInterface) {
                $responses[] = $command->parseResponse($response);
            } elseif ($response instanceof ErrorResponseInterface && $exceptions) {
                $this->exception($connection, $response);
            } else {
                $responses[] = $response;
            }

            unset($executed[$i]);
        }

        return $responses;
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Cluster\Distributor;

use Predis\Cluster\Hash\HashGeneratorInterface;

/**
 * A distributor implements the logic to automatically distribute keys among
 * several nodes for client-side sharding.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface DistributorInterface
{
    /**
     * Adds a node to the distributor with an optional weight.
     *
     * @param mixed $node   Node object.
     * @param int   $weight Weight for the node.
     */
    public function add($node, $weight = null);

    /**
     * Removes a node from the distributor.
     *
     * @param mixed $node Node object.
     */
    public function remove($node);

    /**
     * Returns the corresponding slot of a node from the distributor using the
     * computed hash of a key.
     *
     * @param mixed $hash
     *
     * @return mixed
     */
    public function getSlot($hash);

    /**
     * Returns a node from the distributor using its assigned slot ID.
     *
     * @param mixed $slot
     *
     * @return mixed|null
     */
    public function getBySlot($slot);

    /**
     * Returns a node from the distributor using the computed hash of a key.
     *
     * @param mixed $hash
     *
     * @return mixed
     */
    public function getByHash($hash);

    /**
     * Returns a node from the distributor mapping to the specified value.
     *
     * @param string $value
     *
     * @return mixed
     */
    public function get($value);

    /**
     * Returns the underlying hash generator instance.
     *
     * @return HashGeneratorInterface
     */
    public function getHashGenerator();
}

/**
 * This class implements an hashring-based distributor that uses the same
 * algorithm of memcache to distribute keys in a cluster using client-side
 * sharding.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @author Lorenzo Castelli <lcastelli@gmail.com>
 */
class HashRing implements DistributorInterface, HashGeneratorInterface
{
    const DEFAULT_REPLICAS = 128;
    const DEFAULT_WEIGHT = 100;

    private $ring;
    private $ringKeys;
    private $ringKeysCount;
    private $replicas;
    private $nodeHashCallback;
    private $nodes = array();

    /**
     * @param int   $replicas         Number of replicas in the ring.
     * @param mixed $nodeHashCallback Callback returning a string used to calculate the hash of nodes.
     */
    public function __construct($replicas = self::DEFAULT_REPLICAS, $nodeHashCallback = null)
    {
        $this->replicas = $replicas;
        $this->nodeHashCallback = $nodeHashCallback;
    }

    /**
     * Adds a node to the ring with an optional weight.
     *
     * @param mixed $node   Node object.
     * @param int   $weight Weight for the node.
     */
    public function add($node, $weight = null)
    {
        // In case of collisions in the hashes of the nodes, the node added
        // last wins, thus the order in which nodes are added is significant.
        $this->nodes[] = array(
            'object' => $node,
            'weight' => (int) $weight ?: $this::DEFAULT_WEIGHT,
        );

        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($node)
    {
        // A node is removed by resetting the ring so that it's recreated from
        // scratch, in order to reassign possible hashes with collisions to the
        // right node according to the order in which they were added in the
        // first place.
        for ($i = 0; $i < count($this->nodes); ++$i) {
            if ($this->nodes[$i]['object'] === $node) {
                array_splice($this->nodes, $i, 1);
                $this->reset();

                break;
            }
        }
    }

    /**
     * Resets the distributor.
     */
    private function reset()
    {
        unset(
            $this->ring,
            $this->ringKeys,
            $this->ringKeysCount
        );
    }

    /**
     * Returns the initialization status of the distributor.
     *
     * @return bool
     */
    private function isInitialized()
    {
        return isset($this->ringKeys);
    }

    /**
     * Calculates the total weight of all the nodes in the distributor.
     *
     * @return int
     */
    private function computeTotalWeight()
    {
        $totalWeight = 0;

        foreach ($this->nodes as $node) {
            $totalWeight += $node['weight'];
        }

        return $totalWeight;
    }

    /**
     * Initializes the distributor.
     */
    private function initialize()
    {
        if ($this->isInitialized()) {
            return;
        }

        if (!$this->nodes) {
            throw new EmptyRingException('Cannot initialize an empty hashring.');
        }

        $this->ring = array();
        $totalWeight = $this->computeTotalWeight();
        $nodesCount = count($this->nodes);

        foreach ($this->nodes as $node) {
            $weightRatio = $node['weight'] / $totalWeight;
            $this->addNodeToRing($this->ring, $node, $nodesCount, $this->replicas, $weightRatio);
        }

        ksort($this->ring, SORT_NUMERIC);
        $this->ringKeys = array_keys($this->ring);
        $this->ringKeysCount = count($this->ringKeys);
    }

    /**
     * Implements the logic needed to add a node to the hashring.
     *
     * @param array $ring        Source hashring.
     * @param mixed $node        Node object to be added.
     * @param int   $totalNodes  Total number of nodes.
     * @param int   $replicas    Number of replicas in the ring.
     * @param float $weightRatio Weight ratio for the node.
     */
    protected function addNodeToRing(&$ring, $node, $totalNodes, $replicas, $weightRatio)
    {
        $nodeObject = $node['object'];
        $nodeHash = $this->getNodeHash($nodeObject);
        $replicas = (int) round($weightRatio * $totalNodes * $replicas);

        for ($i = 0; $i < $replicas; ++$i) {
            $key = crc32("$nodeHash:$i");
            $ring[$key] = $nodeObject;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getNodeHash($nodeObject)
    {
        if (!isset($this->nodeHashCallback)) {
            return (string) $nodeObject;
        }

        return call_user_func($this->nodeHashCallback, $nodeObject);
    }

    /**
     * {@inheritdoc}
     */
    public function hash($value)
    {
        return crc32($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getByHash($hash)
    {
        return $this->ring[$this->getSlot($hash)];
    }

    /**
     * {@inheritdoc}
     */
    public function getBySlot($slot)
    {
        $this->initialize();

        if (isset($this->ring[$slot])) {
            return $this->ring[$slot];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSlot($hash)
    {
        $this->initialize();

        $ringKeys = $this->ringKeys;
        $upper = $this->ringKeysCount - 1;
        $lower = 0;

        while ($lower <= $upper) {
            $index = ($lower + $upper) >> 1;
            $item = $ringKeys[$index];

            if ($item > $hash) {
                $upper = $index - 1;
            } elseif ($item < $hash) {
                $lower = $index + 1;
            } else {
                return $item;
            }
        }

        return $ringKeys[$this->wrapAroundStrategy($upper, $lower, $this->ringKeysCount)];
    }

    /**
     * {@inheritdoc}
     */
    public function get($value)
    {
        $hash = $this->hash($value);
        $node = $this->getByHash($hash);

        return $node;
    }

    /**
     * Implements a strategy to deal with wrap-around errors during binary searches.
     *
     * @param int $upper
     * @param int $lower
     * @param int $ringKeysCount
     *
     * @return int
     */
    protected function wrapAroundStrategy($upper, $lower, $ringKeysCount)
    {
        // Binary search for the last item in ringkeys with a value less or
        // equal to the key. If no such item exists, return the last item.
        return $upper >= 0 ? $upper : $ringKeysCount - 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getHashGenerator()
    {
        return $this;
    }
}

/**
 * This class implements an hashring-based distributor that uses the same
 * algorithm of libketama to distribute keys in a cluster using client-side
 * sharding.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @author Lorenzo Castelli <lcastelli@gmail.com>
 */
class KetamaRing extends HashRing
{
    const DEFAULT_REPLICAS = 160;

    /**
     * @param mixed $nodeHashCallback Callback returning a string used to calculate the hash of nodes.
     */
    public function __construct($nodeHashCallback = null)
    {
        parent::__construct($this::DEFAULT_REPLICAS, $nodeHashCallback);
    }

    /**
     * {@inheritdoc}
     */
    protected function addNodeToRing(&$ring, $node, $totalNodes, $replicas, $weightRatio)
    {
        $nodeObject = $node['object'];
        $nodeHash = $this->getNodeHash($nodeObject);
        $replicas = (int) floor($weightRatio * $totalNodes * ($replicas / 4));

        for ($i = 0; $i < $replicas; ++$i) {
            $unpackedDigest = unpack('V4', md5("$nodeHash-$i", true));

            foreach ($unpackedDigest as $key) {
                $ring[$key] = $nodeObject;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hash($value)
    {
        $hash = unpack('V', md5($value, true));

        return $hash[1];
    }

    /**
     * {@inheritdoc}
     */
    protected function wrapAroundStrategy($upper, $lower, $ringKeysCount)
    {
        // Binary search for the first item in ringkeys with a value greater
        // or equal to the key. If no such item exists, return the first item.
        return $lower < $ringKeysCount ? $lower : 0;
    }
}

/**
 * Exception class that identifies empty rings.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class EmptyRingException extends \Exception
{
}

/* --------------------------------------------------------------------------- */

namespace Predis\Response\Iterator;

use Predis\Connection\NodeConnectionInterface;
use Predis\Response\ResponseInterface;

/**
 * Iterator that abstracts the access to multibulk responses allowing them to be
 * consumed in a streamable fashion without keeping the whole payload in memory.
 *
 * This iterator does not support rewinding which means that the iteration, once
 * consumed, cannot be restarted.
 *
 * Always make sure that the whole iteration is consumed (or dropped) to prevent
 * protocol desynchronization issues.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class MultiBulkIterator implements \Iterator, \Countable, ResponseInterface
{
    protected $current;
    protected $position;
    protected $size;

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        // NOOP
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if (++$this->position < $this->size) {
            $this->current = $this->getValue();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->position < $this->size;
    }

    /**
     * Returns the number of items comprising the whole multibulk response.
     *
     * This method should be used instead of iterator_count() to get the size of
     * the current multibulk response since the former consumes the iteration to
     * count the number of elements, but our iterators do not support rewinding.
     *
     * @return int
     */
    public function count()
    {
        return $this->size;
    }

    /**
     * Returns the current position of the iterator.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    abstract protected function getValue();
}

/**
 * Streamable multibulk response.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiBulk extends MultiBulkIterator
{
    private $connection;

    /**
     * @param NodeConnectionInterface $connection Connection to Redis.
     * @param int                     $size       Number of elements of the multibulk response.
     */
    public function __construct(NodeConnectionInterface $connection, $size)
    {
        $this->connection = $connection;
        $this->size = $size;
        $this->position = 0;
        $this->current = $size > 0 ? $this->getValue() : null;
    }

    /**
     * Handles the synchronization of the client with the Redis protocol when
     * the garbage collector kicks in (e.g. when the iterator goes out of the
     * scope of a foreach or it is unset).
     */
    public function __destruct()
    {
        $this->drop(true);
    }

    /**
     * Drop queued elements that have not been read from the connection either
     * by consuming the rest of the multibulk response or quickly by closing the
     * underlying connection.
     *
     * @param bool $disconnect Consume the iterator or drop the connection.
     */
    public function drop($disconnect = false)
    {
        if ($disconnect) {
            if ($this->valid()) {
                $this->position = $this->size;
                $this->connection->disconnect();
            }
        } else {
            while ($this->valid()) {
                $this->next();
            }
        }
    }

    /**
     * Reads the next item of the multibulk response from the connection.
     *
     * @return mixed
     */
    protected function getValue()
    {
        return $this->connection->read();
    }
}

/**
 * Outer iterator consuming streamable multibulk responses by yielding tuples of
 * keys and values.
 *
 * This wrapper is useful for responses to commands such as `HGETALL` that can
 * be iterater as $key => $value pairs.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiBulkTuple extends MultiBulk implements \OuterIterator
{
    private $iterator;

    /**
     * @param MultiBulk $iterator Inner multibulk response iterator.
     */
    public function __construct(MultiBulk $iterator)
    {
        $this->checkPreconditions($iterator);

        $this->size = count($iterator) / 2;
        $this->iterator = $iterator;
        $this->position = $iterator->getPosition();
        $this->current = $this->size > 0 ? $this->getValue() : null;
    }

    /**
     * Checks for valid preconditions.
     *
     * @param MultiBulk $iterator Inner multibulk response iterator.
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    protected function checkPreconditions(MultiBulk $iterator)
    {
        if ($iterator->getPosition() !== 0) {
            throw new \InvalidArgumentException(
                'Cannot initialize a tuple iterator using an already initiated iterator.'
            );
        }

        if (($size = count($iterator)) % 2 !== 0) {
            throw new \UnexpectedValueException('Invalid response size for a tuple iterator.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerIterator()
    {
        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        $this->iterator->drop(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue()
    {
        $k = $this->iterator->current();
        $this->iterator->next();

        $v = $this->iterator->current();
        $this->iterator->next();

        return array($k, $v);
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Cluster\Hash;

/**
 * An hash generator implements the logic used to calculate the hash of a key to
 * distribute operations among Redis nodes.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface HashGeneratorInterface
{
    /**
     * Generates an hash from a string to be used for distribution.
     *
     * @param string $value String value.
     *
     * @return int
     */
    public function hash($value);
}

/**
 * Hash generator implementing the CRC-CCITT-16 algorithm used by redis-cluster.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class CRC16 implements HashGeneratorInterface
{
    private static $CCITT_16 = array(
        0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50A5, 0x60C6, 0x70E7,
        0x8108, 0x9129, 0xA14A, 0xB16B, 0xC18C, 0xD1AD, 0xE1CE, 0xF1EF,
        0x1231, 0x0210, 0x3273, 0x2252, 0x52B5, 0x4294, 0x72F7, 0x62D6,
        0x9339, 0x8318, 0xB37B, 0xA35A, 0xD3BD, 0xC39C, 0xF3FF, 0xE3DE,
        0x2462, 0x3443, 0x0420, 0x1401, 0x64E6, 0x74C7, 0x44A4, 0x5485,
        0xA56A, 0xB54B, 0x8528, 0x9509, 0xE5EE, 0xF5CF, 0xC5AC, 0xD58D,
        0x3653, 0x2672, 0x1611, 0x0630, 0x76D7, 0x66F6, 0x5695, 0x46B4,
        0xB75B, 0xA77A, 0x9719, 0x8738, 0xF7DF, 0xE7FE, 0xD79D, 0xC7BC,
        0x48C4, 0x58E5, 0x6886, 0x78A7, 0x0840, 0x1861, 0x2802, 0x3823,
        0xC9CC, 0xD9ED, 0xE98E, 0xF9AF, 0x8948, 0x9969, 0xA90A, 0xB92B,
        0x5AF5, 0x4AD4, 0x7AB7, 0x6A96, 0x1A71, 0x0A50, 0x3A33, 0x2A12,
        0xDBFD, 0xCBDC, 0xFBBF, 0xEB9E, 0x9B79, 0x8B58, 0xBB3B, 0xAB1A,
        0x6CA6, 0x7C87, 0x4CE4, 0x5CC5, 0x2C22, 0x3C03, 0x0C60, 0x1C41,
        0xEDAE, 0xFD8F, 0xCDEC, 0xDDCD, 0xAD2A, 0xBD0B, 0x8D68, 0x9D49,
        0x7E97, 0x6EB6, 0x5ED5, 0x4EF4, 0x3E13, 0x2E32, 0x1E51, 0x0E70,
        0xFF9F, 0xEFBE, 0xDFDD, 0xCFFC, 0xBF1B, 0xAF3A, 0x9F59, 0x8F78,
        0x9188, 0x81A9, 0xB1CA, 0xA1EB, 0xD10C, 0xC12D, 0xF14E, 0xE16F,
        0x1080, 0x00A1, 0x30C2, 0x20E3, 0x5004, 0x4025, 0x7046, 0x6067,
        0x83B9, 0x9398, 0xA3FB, 0xB3DA, 0xC33D, 0xD31C, 0xE37F, 0xF35E,
        0x02B1, 0x1290, 0x22F3, 0x32D2, 0x4235, 0x5214, 0x6277, 0x7256,
        0xB5EA, 0xA5CB, 0x95A8, 0x8589, 0xF56E, 0xE54F, 0xD52C, 0xC50D,
        0x34E2, 0x24C3, 0x14A0, 0x0481, 0x7466, 0x6447, 0x5424, 0x4405,
        0xA7DB, 0xB7FA, 0x8799, 0x97B8, 0xE75F, 0xF77E, 0xC71D, 0xD73C,
        0x26D3, 0x36F2, 0x0691, 0x16B0, 0x6657, 0x7676, 0x4615, 0x5634,
        0xD94C, 0xC96D, 0xF90E, 0xE92F, 0x99C8, 0x89E9, 0xB98A, 0xA9AB,
        0x5844, 0x4865, 0x7806, 0x6827, 0x18C0, 0x08E1, 0x3882, 0x28A3,
        0xCB7D, 0xDB5C, 0xEB3F, 0xFB1E, 0x8BF9, 0x9BD8, 0xABBB, 0xBB9A,
        0x4A75, 0x5A54, 0x6A37, 0x7A16, 0x0AF1, 0x1AD0, 0x2AB3, 0x3A92,
        0xFD2E, 0xED0F, 0xDD6C, 0xCD4D, 0xBDAA, 0xAD8B, 0x9DE8, 0x8DC9,
        0x7C26, 0x6C07, 0x5C64, 0x4C45, 0x3CA2, 0x2C83, 0x1CE0, 0x0CC1,
        0xEF1F, 0xFF3E, 0xCF5D, 0xDF7C, 0xAF9B, 0xBFBA, 0x8FD9, 0x9FF8,
        0x6E17, 0x7E36, 0x4E55, 0x5E74, 0x2E93, 0x3EB2, 0x0ED1, 0x1EF0,
    );

    /**
     * {@inheritdoc}
     */
    public function hash($value)
    {
        // CRC-CCITT-16 algorithm
        $crc = 0;
        $CCITT_16 = self::$CCITT_16;
        $strlen = strlen($value);

        for ($i = 0; $i < $strlen; ++$i) {
            $crc = (($crc << 8) ^ $CCITT_16[($crc >> 8) ^ ord($value[$i])]) & 0xFFFF;
        }

        return $crc;
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Command\Processor;

use Predis\Command\CommandInterface;
use Predis\Command\PrefixableCommandInterface;

/**
 * A command processor processes Redis commands before they are sent to Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ProcessorInterface
{
    /**
     * Processes the given Redis command.
     *
     * @param CommandInterface $command Command instance.
     */
    public function process(CommandInterface $command);
}

/**
 * Default implementation of a command processors chain.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ProcessorChain implements \ArrayAccess, ProcessorInterface
{
    private $processors = array();

    /**
     * @param array $processors List of instances of ProcessorInterface.
     */
    public function __construct($processors = array())
    {
        foreach ($processors as $processor) {
            $this->add($processor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(ProcessorInterface $processor)
    {
        $this->processors[] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ProcessorInterface $processor)
    {
        if (false !== $index = array_search($processor, $this->processors, true)) {
            unset($this[$index]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(CommandInterface $command)
    {
        for ($i = 0; $i < $count = count($this->processors); ++$i) {
            $this->processors[$i]->process($command);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * Returns an iterator over the list of command processor in the chain.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->processors);
    }

    /**
     * Returns the number of command processors in the chain.
     *
     * @return int
     */
    public function count()
    {
        return count($this->processors);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($index)
    {
        return isset($this->processors[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($index)
    {
        return $this->processors[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($index, $processor)
    {
        if (!$processor instanceof ProcessorInterface) {
            throw new \InvalidArgumentException(
                'A processor chain accepts only instances of '.
                "'Predis\Command\Processor\ProcessorInterface'."
            );
        }

        $this->processors[$index] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($index)
    {
        unset($this->processors[$index]);
        $this->processors = array_values($this->processors);
    }
}

/**
 * Command processor capable of prefixing keys stored in the arguments of Redis
 * commands supported.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyPrefixProcessor implements ProcessorInterface
{
    private $prefix;
    private $commands;

    /**
     * @param string $prefix Prefix for the keys.
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
        $this->commands = array(
            /* ---------------- Redis 1.2 ---------------- */
            'EXISTS' => 'static::first',
            'DEL' => 'static::all',
            'TYPE' => 'static::first',
            'KEYS' => 'static::first',
            'RENAME' => 'static::all',
            'RENAMENX' => 'static::all',
            'EXPIRE' => 'static::first',
            'EXPIREAT' => 'static::first',
            'TTL' => 'static::first',
            'MOVE' => 'static::first',
            'SORT' => 'static::sort',
            'DUMP' => 'static::first',
            'RESTORE' => 'static::first',
            'SET' => 'static::first',
            'SETNX' => 'static::first',
            'MSET' => 'static::interleaved',
            'MSETNX' => 'static::interleaved',
            'GET' => 'static::first',
            'MGET' => 'static::all',
            'GETSET' => 'static::first',
            'INCR' => 'static::first',
            'INCRBY' => 'static::first',
            'DECR' => 'static::first',
            'DECRBY' => 'static::first',
            'RPUSH' => 'static::first',
            'LPUSH' => 'static::first',
            'LLEN' => 'static::first',
            'LRANGE' => 'static::first',
            'LTRIM' => 'static::first',
            'LINDEX' => 'static::first',
            'LSET' => 'static::first',
            'LREM' => 'static::first',
            'LPOP' => 'static::first',
            'RPOP' => 'static::first',
            'RPOPLPUSH' => 'static::all',
            'SADD' => 'static::first',
            'SREM' => 'static::first',
            'SPOP' => 'static::first',
            'SMOVE' => 'static::skipLast',
            'SCARD' => 'static::first',
            'SISMEMBER' => 'static::first',
            'SINTER' => 'static::all',
            'SINTERSTORE' => 'static::all',
            'SUNION' => 'static::all',
            'SUNIONSTORE' => 'static::all',
            'SDIFF' => 'static::all',
            'SDIFFSTORE' => 'static::all',
            'SMEMBERS' => 'static::first',
            'SRANDMEMBER' => 'static::first',
            'ZADD' => 'static::first',
            'ZINCRBY' => 'static::first',
            'ZREM' => 'static::first',
            'ZRANGE' => 'static::first',
            'ZREVRANGE' => 'static::first',
            'ZRANGEBYSCORE' => 'static::first',
            'ZCARD' => 'static::first',
            'ZSCORE' => 'static::first',
            'ZREMRANGEBYSCORE' => 'static::first',
            /* ---------------- Redis 2.0 ---------------- */
            'SETEX' => 'static::first',
            'APPEND' => 'static::first',
            'SUBSTR' => 'static::first',
            'BLPOP' => 'static::skipLast',
            'BRPOP' => 'static::skipLast',
            'ZUNIONSTORE' => 'static::zsetStore',
            'ZINTERSTORE' => 'static::zsetStore',
            'ZCOUNT' => 'static::first',
            'ZRANK' => 'static::first',
            'ZREVRANK' => 'static::first',
            'ZREMRANGEBYRANK' => 'static::first',
            'HSET' => 'static::first',
            'HSETNX' => 'static::first',
            'HMSET' => 'static::first',
            'HINCRBY' => 'static::first',
            'HGET' => 'static::first',
            'HMGET' => 'static::first',
            'HDEL' => 'static::first',
            'HEXISTS' => 'static::first',
            'HLEN' => 'static::first',
            'HKEYS' => 'static::first',
            'HVALS' => 'static::first',
            'HGETALL' => 'static::first',
            'SUBSCRIBE' => 'static::all',
            'UNSUBSCRIBE' => 'static::all',
            'PSUBSCRIBE' => 'static::all',
            'PUNSUBSCRIBE' => 'static::all',
            'PUBLISH' => 'static::first',
            /* ---------------- Redis 2.2 ---------------- */
            'PERSIST' => 'static::first',
            'STRLEN' => 'static::first',
            'SETRANGE' => 'static::first',
            'GETRANGE' => 'static::first',
            'SETBIT' => 'static::first',
            'GETBIT' => 'static::first',
            'RPUSHX' => 'static::first',
            'LPUSHX' => 'static::first',
            'LINSERT' => 'static::first',
            'BRPOPLPUSH' => 'static::skipLast',
            'ZREVRANGEBYSCORE' => 'static::first',
            'WATCH' => 'static::all',
            /* ---------------- Redis 2.6 ---------------- */
            'PTTL' => 'static::first',
            'PEXPIRE' => 'static::first',
            'PEXPIREAT' => 'static::first',
            'PSETEX' => 'static::first',
            'INCRBYFLOAT' => 'static::first',
            'BITOP' => 'static::skipFirst',
            'BITCOUNT' => 'static::first',
            'HINCRBYFLOAT' => 'static::first',
            'EVAL' => 'static::evalKeys',
            'EVALSHA' => 'static::evalKeys',
            'MIGRATE' => 'static::migrate',
            /* ---------------- Redis 2.8 ---------------- */
            'SSCAN' => 'static::first',
            'ZSCAN' => 'static::first',
            'HSCAN' => 'static::first',
            'PFADD' => 'static::first',
            'PFCOUNT' => 'static::all',
            'PFMERGE' => 'static::all',
            'ZLEXCOUNT' => 'static::first',
            'ZRANGEBYLEX' => 'static::first',
            'ZREMRANGEBYLEX' => 'static::first',
            'ZREVRANGEBYLEX' => 'static::first',
            'BITPOS' => 'static::first',
            /* ---------------- Redis 3.2 ---------------- */
            'HSTRLEN' => 'static::first',
        );
    }

    /**
     * Sets a prefix that is applied to all the keys.
     *
     * @param string $prefix Prefix for the keys.
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Gets the current prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function process(CommandInterface $command)
    {
        if ($command instanceof PrefixableCommandInterface) {
            $command->prefixKeys($this->prefix);
        } elseif (isset($this->commands[$commandID = strtoupper($command->getId())])) {
            call_user_func($this->commands[$commandID], $command, $this->prefix);
        }
    }

    /**
     * Sets an handler for the specified command ID.
     *
     * The callback signature must have 2 parameters of the following types:
     *
     *   - Predis\Command\CommandInterface (command instance)
     *   - String (prefix)
     *
     * When the callback argument is omitted or NULL, the previously
     * associated handler for the specified command ID is removed.
     *
     * @param string $commandID The ID of the command to be handled.
     * @param mixed  $callback  A valid callable object or NULL.
     *
     * @throws \InvalidArgumentException
     */
    public function setCommandHandler($commandID, $callback = null)
    {
        $commandID = strtoupper($commandID);

        if (!isset($callback)) {
            unset($this->commands[$commandID]);

            return;
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(
                'Callback must be a valid callable object or NULL'
            );
        }

        $this->commands[$commandID] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getPrefix();
    }

    /**
     * Applies the specified prefix only the first argument.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function first(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[0] = "$prefix{$arguments[0]}";
            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to all the arguments.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function all(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            foreach ($arguments as &$key) {
                $key = "$prefix$key";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix only to even arguments in the list.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function interleaved(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $length = count($arguments);

            for ($i = 0; $i < $length; $i += 2) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to all the arguments but the first one.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function skipFirst(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $length = count($arguments);

            for ($i = 1; $i < $length; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to all the arguments but the last one.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function skipLast(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $length = count($arguments);

            for ($i = 0; $i < $length - 1; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the keys of a SORT command.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function sort(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[0] = "$prefix{$arguments[0]}";

            if (($count = count($arguments)) > 1) {
                for ($i = 1; $i < $count; ++$i) {
                    switch ($arguments[$i]) {
                        case 'BY':
                        case 'STORE':
                            $arguments[$i] = "$prefix{$arguments[++$i]}";
                            break;

                        case 'GET':
                            $value = $arguments[++$i];
                            if ($value !== '#') {
                                $arguments[$i] = "$prefix$value";
                            }
                            break;

                        case 'LIMIT';
                            $i += 2;
                            break;
                    }
                }
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the keys of an EVAL-based command.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function evalKeys(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            for ($i = 2; $i < $arguments[1] + 2; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the keys of Z[INTERSECTION|UNION]STORE.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function zsetStore(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[0] = "$prefix{$arguments[0]}";
            $length = ((int) $arguments[1]) + 2;

            for ($i = 2; $i < $length; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the key of a MIGRATE command.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function migrate(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[2] = "$prefix{$arguments[2]}";
            $command->setRawArguments($arguments);
        }
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Protocol\Text;

use Predis\Command\CommandInterface;
use Predis\Connection\CompositeConnectionInterface;
use Predis\Protocol\ProtocolProcessorInterface;
use Predis\Protocol\RequestSerializerInterface;
use Predis\Protocol\ResponseReaderInterface;
use Predis\CommunicationException;
use Predis\Protocol\ProtocolException;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\Iterator\MultiBulk as MultiBulkIterator;
use Predis\Response\Status as StatusResponse;

/**
 * Response reader for the standard Redis wire protocol.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ResponseReader implements ResponseReaderInterface
{
    protected $handlers;

    /**
     *
     */
    public function __construct()
    {
        $this->handlers = $this->getDefaultHandlers();
    }

    /**
     * Returns the default handlers for the supported type of responses.
     *
     * @return array
     */
    protected function getDefaultHandlers()
    {
        return array(
            '+' => new Handler\StatusResponse(),
            '-' => new Handler\ErrorResponse(),
            ':' => new Handler\IntegerResponse(),
            '$' => new Handler\BulkResponse(),
            '*' => new Handler\MultiBulkResponse(),
        );
    }

    /**
     * Sets the handler for the specified prefix identifying the response type.
     *
     * @param string                           $prefix  Identifier of the type of response.
     * @param Handler\ResponseHandlerInterface $handler Response handler.
     */
    public function setHandler($prefix, Handler\ResponseHandlerInterface $handler)
    {
        $this->handlers[$prefix] = $handler;
    }

    /**
     * Returns the response handler associated to a certain type of response.
     *
     * @param string $prefix Identifier of the type of response.
     *
     * @return Handler\ResponseHandlerInterface
     */
    public function getHandler($prefix)
    {
        if (isset($this->handlers[$prefix])) {
            return $this->handlers[$prefix];
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function read(CompositeConnectionInterface $connection)
    {
        $header = $connection->readLine();

        if ($header === '') {
            $this->onProtocolError($connection, 'Unexpected empty reponse header.');
        }

        $prefix = $header[0];

        if (!isset($this->handlers[$prefix])) {
            $this->onProtocolError($connection, "Unknown response prefix: '$prefix'.");
        }

        $payload = $this->handlers[$prefix]->handle($connection, substr($header, 1));

        return $payload;
    }

    /**
     * Handles protocol errors generated while reading responses from a
     * connection.
     *
     * @param CompositeConnectionInterface $connection Redis connection that generated the error.
     * @param string                       $message    Error message.
     */
    protected function onProtocolError(CompositeConnectionInterface $connection, $message)
    {
        CommunicationException::handle(
            new ProtocolException($connection, $message)
        );
    }
}

/**
 * Request serializer for the standard Redis wire protocol.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RequestSerializer implements RequestSerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize(CommandInterface $command)
    {
        $commandID = $command->getId();
        $arguments = $command->getArguments();

        $cmdlen = strlen($commandID);
        $reqlen = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandID}\r\n";

        for ($i = 0, $reqlen--; $i < $reqlen; ++$i) {
            $argument = $arguments[$i];
            $arglen = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        return $buffer;
    }
}

/**
 * Protocol processor for the standard Redis wire protocol.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ProtocolProcessor implements ProtocolProcessorInterface
{
    protected $mbiterable;
    protected $serializer;

    /**
     *
     */
    public function __construct()
    {
        $this->mbiterable = false;
        $this->serializer = new RequestSerializer();
    }

    /**
     * {@inheritdoc}
     */
    public function write(CompositeConnectionInterface $connection, CommandInterface $command)
    {
        $request = $this->serializer->serialize($command);
        $connection->writeBuffer($request);
    }

    /**
     * {@inheritdoc}
     */
    public function read(CompositeConnectionInterface $connection)
    {
        $chunk = $connection->readLine();
        $prefix = $chunk[0];
        $payload = substr($chunk, 1);

        switch ($prefix) {
            case '+':
                return new StatusResponse($payload);

            case '$':
                $size = (int) $payload;
                if ($size === -1) {
                    return;
                }

                return substr($connection->readBuffer($size + 2), 0, -2);

            case '*':
                $count = (int) $payload;

                if ($count === -1) {
                    return;
                }
                if ($this->mbiterable) {
                    return new MultiBulkIterator($connection, $count);
                }

                $multibulk = array();

                for ($i = 0; $i < $count; ++$i) {
                    $multibulk[$i] = $this->read($connection);
                }

                return $multibulk;

            case ':':
                return (int) $payload;

            case '-':
                return new ErrorResponse($payload);

            default:
                CommunicationException::handle(new ProtocolException(
                    $connection, "Unknown response prefix: '$prefix'."
                ));

                return;
        }
    }

    /**
     * Enables or disables returning multibulk responses as specialized PHP
     * iterators used to stream bulk elements of a multibulk response instead
     * returning a plain array.
     *
     * Streamable multibulk responses are not globally supported by the
     * abstractions built-in into Predis, such as transactions or pipelines.
     * Use them with care!
     *
     * @param bool $value Enable or disable streamable multibulk responses.
     */
    public function useIterableMultibulk($value)
    {
        $this->mbiterable = (bool) $value;
    }
}

/**
 * Composite protocol processor for the standard Redis wire protocol using
 * pluggable handlers to serialize requests and deserialize responses.
 *
 * @link http://redis.io/topics/protocol
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class CompositeProtocolProcessor implements ProtocolProcessorInterface
{
    /*
     * @var RequestSerializerInterface
     */
    protected $serializer;

    /*
     * @var ResponseReaderInterface
     */
    protected $reader;

    /**
     * @param RequestSerializerInterface $serializer Request serializer.
     * @param ResponseReaderInterface    $reader     Response reader.
     */
    public function __construct(
        RequestSerializerInterface $serializer = null,
        ResponseReaderInterface $reader = null
    ) {
        $this->setRequestSerializer($serializer ?: new RequestSerializer());
        $this->setResponseReader($reader ?: new ResponseReader());
    }

    /**
     * {@inheritdoc}
     */
    public function write(CompositeConnectionInterface $connection, CommandInterface $command)
    {
        $connection->writeBuffer($this->serializer->serialize($command));
    }

    /**
     * {@inheritdoc}
     */
    public function read(CompositeConnectionInterface $connection)
    {
        return $this->reader->read($connection);
    }

    /**
     * Sets the request serializer used by the protocol processor.
     *
     * @param RequestSerializerInterface $serializer Request serializer.
     */
    public function setRequestSerializer(RequestSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Returns the request serializer used by the protocol processor.
     *
     * @return RequestSerializerInterface
     */
    public function getRequestSerializer()
    {
        return $this->serializer;
    }

    /**
     * Sets the response reader used by the protocol processor.
     *
     * @param ResponseReaderInterface $reader Response reader.
     */
    public function setResponseReader(ResponseReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Returns the Response reader used by the protocol processor.
     *
     * @return ResponseReaderInterface
     */
    public function getResponseReader()
    {
        return $this->reader;
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\PubSub;

use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\Command;
use Predis\Connection\AggregateConnectionInterface;
use Predis\NotSupportedException;

/**
 * Base implementation of a PUB/SUB consumer abstraction based on PHP iterators.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class AbstractConsumer implements \Iterator
{
    const SUBSCRIBE    = 'subscribe';
    const UNSUBSCRIBE  = 'unsubscribe';
    const PSUBSCRIBE   = 'psubscribe';
    const PUNSUBSCRIBE = 'punsubscribe';
    const MESSAGE      = 'message';
    const PMESSAGE     = 'pmessage';
    const PONG         = 'pong';

    const STATUS_VALID       = 1;    // 0b0001
    const STATUS_SUBSCRIBED  = 2;    // 0b0010
    const STATUS_PSUBSCRIBED = 4;    // 0b0100

    private $position = null;
    private $statusFlags = self::STATUS_VALID;

    /**
     * Automatically stops the consumer when the garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->stop(true);
    }

    /**
     * Checks if the specified flag is valid based on the state of the consumer.
     *
     * @param int $value Flag.
     *
     * @return bool
     */
    protected function isFlagSet($value)
    {
        return ($this->statusFlags & $value) === $value;
    }

    /**
     * Subscribes to the specified channels.
     *
     * @param mixed $channel,... One or more channel names.
     */
    public function subscribe($channel /*, ... */)
    {
        $this->writeRequest(self::SUBSCRIBE, func_get_args());
        $this->statusFlags |= self::STATUS_SUBSCRIBED;
    }

    /**
     * Unsubscribes from the specified channels.
     *
     * @param string ... One or more channel names.
     */
    public function unsubscribe(/* ... */)
    {
        $this->writeRequest(self::UNSUBSCRIBE, func_get_args());
    }

    /**
     * Subscribes to the specified channels using a pattern.
     *
     * @param mixed $pattern,... One or more channel name patterns.
     */
    public function psubscribe($pattern /* ... */)
    {
        $this->writeRequest(self::PSUBSCRIBE, func_get_args());
        $this->statusFlags |= self::STATUS_PSUBSCRIBED;
    }

    /**
     * Unsubscribes from the specified channels using a pattern.
     *
     * @param string ... One or more channel name patterns.
     */
    public function punsubscribe(/* ... */)
    {
        $this->writeRequest(self::PUNSUBSCRIBE, func_get_args());
    }

    /**
     * PING the server with an optional payload that will be echoed as a
     * PONG message in the pub/sub loop.
     *
     * @param string $payload Optional PING payload.
     */
    public function ping($payload = null)
    {
        $this->writeRequest('PING', array($payload));
    }

    /**
     * Closes the context by unsubscribing from all the subscribed channels. The
     * context can be forcefully closed by dropping the underlying connection.
     *
     * @param bool $drop Indicates if the context should be closed by dropping the connection.
     *
     * @return bool Returns false when there are no pending messages.
     */
    public function stop($drop = false)
    {
        if (!$this->valid()) {
            return false;
        }

        if ($drop) {
            $this->invalidate();
            $this->disconnect();
        } else {
            if ($this->isFlagSet(self::STATUS_SUBSCRIBED)) {
                $this->unsubscribe();
            }
            if ($this->isFlagSet(self::STATUS_PSUBSCRIBED)) {
                $this->punsubscribe();
            }
        }

        return !$drop;
    }

    /**
     * Closes the underlying connection when forcing a disconnection.
     */
    abstract protected function disconnect();

    /**
     * Writes a Redis command on the underlying connection.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     */
    abstract protected function writeRequest($method, $arguments);

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        // NOOP
    }

    /**
     * Returns the last message payload retrieved from the server and generated
     * by one of the active subscriptions.
     *
     * @return array
     */
    public function current()
    {
        return $this->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if ($this->valid()) {
            ++$this->position;
        }

        return $this->position;
    }

    /**
     * Checks if the the consumer is still in a valid state to continue.
     *
     * @return bool
     */
    public function valid()
    {
        $isValid = $this->isFlagSet(self::STATUS_VALID);
        $subscriptionFlags = self::STATUS_SUBSCRIBED | self::STATUS_PSUBSCRIBED;
        $hasSubscriptions = ($this->statusFlags & $subscriptionFlags) > 0;

        return $isValid && $hasSubscriptions;
    }

    /**
     * Resets the state of the consumer.
     */
    protected function invalidate()
    {
        $this->statusFlags = 0;    // 0b0000;
    }

    /**
     * Waits for a new message from the server generated by one of the active
     * subscriptions and returns it when available.
     *
     * @return array
     */
    abstract protected function getValue();
}

/**
 * Method-dispatcher loop built around the client-side abstraction of a Redis
 * PUB / SUB context.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class DispatcherLoop
{
    private $pubsub;

    protected $callbacks;
    protected $defaultCallback;
    protected $subscriptionCallback;

    /**
     * @param Consumer $pubsub PubSub consumer instance used by the loop.
     */
    public function __construct(Consumer $pubsub)
    {
        $this->callbacks = array();
        $this->pubsub = $pubsub;
    }

    /**
     * Checks if the passed argument is a valid callback.
     *
     * @param mixed $callable A callback.
     *
     * @throws \InvalidArgumentException
     */
    protected function assertCallback($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException('The given argument must be a callable object.');
        }
    }

    /**
     * Returns the underlying PUB / SUB context.
     *
     * @return Consumer
     */
    public function getPubSubConsumer()
    {
        return $this->pubsub;
    }

    /**
     * Sets a callback that gets invoked upon new subscriptions.
     *
     * @param mixed $callable A callback.
     */
    public function subscriptionCallback($callable = null)
    {
        if (isset($callable)) {
            $this->assertCallback($callable);
        }

        $this->subscriptionCallback = $callable;
    }

    /**
     * Sets a callback that gets invoked when a message is received on a
     * channel that does not have an associated callback.
     *
     * @param mixed $callable A callback.
     */
    public function defaultCallback($callable = null)
    {
        if (isset($callable)) {
            $this->assertCallback($callable);
        }

        $this->subscriptionCallback = $callable;
    }

    /**
     * Binds a callback to a channel.
     *
     * @param string   $channel  Channel name.
     * @param Callable $callback A callback.
     */
    public function attachCallback($channel, $callback)
    {
        $callbackName = $this->getPrefixKeys().$channel;

        $this->assertCallback($callback);
        $this->callbacks[$callbackName] = $callback;
        $this->pubsub->subscribe($channel);
    }

    /**
     * Stops listening to a channel and removes the associated callback.
     *
     * @param string $channel Redis channel.
     */
    public function detachCallback($channel)
    {
        $callbackName = $this->getPrefixKeys().$channel;

        if (isset($this->callbacks[$callbackName])) {
            unset($this->callbacks[$callbackName]);
            $this->pubsub->unsubscribe($channel);
        }
    }

    /**
     * Starts the dispatcher loop.
     */
    public function run()
    {
        foreach ($this->pubsub as $message) {
            $kind = $message->kind;

            if ($kind !== Consumer::MESSAGE && $kind !== Consumer::PMESSAGE) {
                if (isset($this->subscriptionCallback)) {
                    $callback = $this->subscriptionCallback;
                    call_user_func($callback, $message);
                }

                continue;
            }

            if (isset($this->callbacks[$message->channel])) {
                $callback = $this->callbacks[$message->channel];
                call_user_func($callback, $message->payload);
            } elseif (isset($this->defaultCallback)) {
                $callback = $this->defaultCallback;
                call_user_func($callback, $message);
            }
        }
    }

    /**
     * Terminates the dispatcher loop.
     */
    public function stop()
    {
        $this->pubsub->stop();
    }

    /**
     * Return the prefix used for keys.
     *
     * @return string
     */
    protected function getPrefixKeys()
    {
        $options = $this->pubsub->getClient()->getOptions();

        if (isset($options->prefix)) {
            return $options->prefix->getPrefix();
        }

        return '';
    }
}

/**
 * PUB/SUB consumer abstraction.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Consumer extends AbstractConsumer
{
    private $client;
    private $options;

    /**
     * @param ClientInterface $client  Client instance used by the consumer.
     * @param array           $options Options for the consumer initialization.
     */
    public function __construct(ClientInterface $client, array $options = null)
    {
        $this->checkCapabilities($client);

        $this->options = $options ?: array();
        $this->client = $client;

        $this->genericSubscribeInit('subscribe');
        $this->genericSubscribeInit('psubscribe');
    }

    /**
     * Returns the underlying client instance used by the pub/sub iterator.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Checks if the client instance satisfies the required conditions needed to
     * initialize a PUB/SUB consumer.
     *
     * @param ClientInterface $client Client instance used by the consumer.
     *
     * @throws NotSupportedException
     */
    private function checkCapabilities(ClientInterface $client)
    {
        if ($client->getConnection() instanceof AggregateConnectionInterface) {
            throw new NotSupportedException(
                'Cannot initialize a PUB/SUB consumer over aggregate connections.'
            );
        }

        $commands = array('publish', 'subscribe', 'unsubscribe', 'psubscribe', 'punsubscribe');

        if ($client->getProfile()->supportsCommands($commands) === false) {
            throw new NotSupportedException(
                'The current profile does not support PUB/SUB related commands.'
            );
        }
    }

    /**
     * This method shares the logic to handle both SUBSCRIBE and PSUBSCRIBE.
     *
     * @param string $subscribeAction Type of subscription.
     */
    private function genericSubscribeInit($subscribeAction)
    {
        if (isset($this->options[$subscribeAction])) {
            $this->$subscribeAction($this->options[$subscribeAction]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function writeRequest($method, $arguments)
    {
        $this->client->getConnection()->writeRequest(
            $this->client->createCommand($method,
                Command::normalizeArguments($arguments)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function disconnect()
    {
        $this->client->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue()
    {
        $response = $this->client->getConnection()->read();

        switch ($response[0]) {
            case self::SUBSCRIBE:
            case self::UNSUBSCRIBE:
            case self::PSUBSCRIBE:
            case self::PUNSUBSCRIBE:
                if ($response[2] === 0) {
                    $this->invalidate();
                }
                // The missing break here is intentional as we must process
                // subscriptions and unsubscriptions as standard messages.
                // no break

            case self::MESSAGE:
                return (object) array(
                    'kind' => $response[0],
                    'channel' => $response[1],
                    'payload' => $response[2],
                );

            case self::PMESSAGE:
                return (object) array(
                    'kind' => $response[0],
                    'pattern' => $response[1],
                    'channel' => $response[2],
                    'payload' => $response[3],
                );

            case self::PONG:
                return (object) array(
                    'kind' => $response[0],
                    'payload' => $response[1],
                );

            default:
                throw new ClientException(
                    "Unknown message type '{$response[0]}' received in the PUB/SUB context."
                );
        }
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Transaction;

use Predis\PredisException;
use Predis\ClientContextInterface;
use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\CommunicationException;
use Predis\Connection\AggregateConnectionInterface;
use Predis\NotSupportedException;
use Predis\Protocol\ProtocolException;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ServerException;
use Predis\Response\Status as StatusResponse;

/**
 * Utility class used to track the state of a MULTI / EXEC transaction.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiExecState
{
    const INITIALIZED = 1;    // 0b00001
    const INSIDEBLOCK = 2;    // 0b00010
    const DISCARDED   = 4;    // 0b00100
    const CAS         = 8;    // 0b01000
    const WATCH       = 16;   // 0b10000

    private $flags;

    /**
     *
     */
    public function __construct()
    {
        $this->flags = 0;
    }

    /**
     * Sets the internal state flags.
     *
     * @param int $flags Set of flags
     */
    public function set($flags)
    {
        $this->flags = $flags;
    }

    /**
     * Gets the internal state flags.
     *
     * @return int
     */
    public function get()
    {
        return $this->flags;
    }

    /**
     * Sets one or more flags.
     *
     * @param int $flags Set of flags
     */
    public function flag($flags)
    {
        $this->flags |= $flags;
    }

    /**
     * Resets one or more flags.
     *
     * @param int $flags Set of flags
     */
    public function unflag($flags)
    {
        $this->flags &= ~$flags;
    }

    /**
     * Returns if the specified flag or set of flags is set.
     *
     * @param int $flags Flag
     *
     * @return bool
     */
    public function check($flags)
    {
        return ($this->flags & $flags) === $flags;
    }

    /**
     * Resets the state of a transaction.
     */
    public function reset()
    {
        $this->flags = 0;
    }

    /**
     * Returns the state of the RESET flag.
     *
     * @return bool
     */
    public function isReset()
    {
        return $this->flags === 0;
    }

    /**
     * Returns the state of the INITIALIZED flag.
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->check(self::INITIALIZED);
    }

    /**
     * Returns the state of the INSIDEBLOCK flag.
     *
     * @return bool
     */
    public function isExecuting()
    {
        return $this->check(self::INSIDEBLOCK);
    }

    /**
     * Returns the state of the CAS flag.
     *
     * @return bool
     */
    public function isCAS()
    {
        return $this->check(self::CAS);
    }

    /**
     * Returns if WATCH is allowed in the current state.
     *
     * @return bool
     */
    public function isWatchAllowed()
    {
        return $this->check(self::INITIALIZED) && !$this->check(self::CAS);
    }

    /**
     * Returns the state of the WATCH flag.
     *
     * @return bool
     */
    public function isWatching()
    {
        return $this->check(self::WATCH);
    }

    /**
     * Returns the state of the DISCARDED flag.
     *
     * @return bool
     */
    public function isDiscarded()
    {
        return $this->check(self::DISCARDED);
    }
}

/**
 * Client-side abstraction of a Redis transaction based on MULTI / EXEC.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiExec implements ClientContextInterface
{
    private $state;

    protected $client;
    protected $commands;
    protected $exceptions = true;
    protected $attempts = 0;
    protected $watchKeys = array();
    protected $modeCAS = false;

    /**
     * @param ClientInterface $client  Client instance used by the transaction.
     * @param array           $options Initialization options.
     */
    public function __construct(ClientInterface $client, array $options = null)
    {
        $this->assertClient($client);

        $this->client = $client;
        $this->state = new MultiExecState();

        $this->configure($client, $options ?: array());
        $this->reset();
    }

    /**
     * Checks if the passed client instance satisfies the required conditions
     * needed to initialize the transaction object.
     *
     * @param ClientInterface $client Client instance used by the transaction object.
     *
     * @throws NotSupportedException
     */
    private function assertClient(ClientInterface $client)
    {
        if ($client->getConnection() instanceof AggregateConnectionInterface) {
            throw new NotSupportedException(
                'Cannot initialize a MULTI/EXEC transaction over aggregate connections.'
            );
        }

        if (!$client->getProfile()->supportsCommands(array('MULTI', 'EXEC', 'DISCARD'))) {
            throw new NotSupportedException(
                'The current profile does not support MULTI, EXEC and DISCARD.'
            );
        }
    }

    /**
     * Configures the transaction using the provided options.
     *
     * @param ClientInterface $client  Underlying client instance.
     * @param array           $options Array of options for the transaction.
     **/
    protected function configure(ClientInterface $client, array $options)
    {
        if (isset($options['exceptions'])) {
            $this->exceptions = (bool) $options['exceptions'];
        } else {
            $this->exceptions = $client->getOptions()->exceptions;
        }

        if (isset($options['cas'])) {
            $this->modeCAS = (bool) $options['cas'];
        }

        if (isset($options['watch']) && $keys = $options['watch']) {
            $this->watchKeys = $keys;
        }

        if (isset($options['retry'])) {
            $this->attempts = (int) $options['retry'];
        }
    }

    /**
     * Resets the state of the transaction.
     */
    protected function reset()
    {
        $this->state->reset();
        $this->commands = new \SplQueue();
    }

    /**
     * Initializes the transaction context.
     */
    protected function initialize()
    {
        if ($this->state->isInitialized()) {
            return;
        }

        if ($this->modeCAS) {
            $this->state->flag(MultiExecState::CAS);
        }

        if ($this->watchKeys) {
            $this->watch($this->watchKeys);
        }

        $cas = $this->state->isCAS();
        $discarded = $this->state->isDiscarded();

        if (!$cas || ($cas && $discarded)) {
            $this->call('MULTI');

            if ($discarded) {
                $this->state->unflag(MultiExecState::CAS);
            }
        }

        $this->state->unflag(MultiExecState::DISCARDED);
        $this->state->flag(MultiExecState::INITIALIZED);
    }

    /**
     * Dynamically invokes a Redis command with the specified arguments.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->executeCommand(
            $this->client->createCommand($method, $arguments)
        );
    }

    /**
     * Executes a Redis command bypassing the transaction logic.
     *
     * @param string $commandID Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @throws ServerException
     *
     * @return mixed
     */
    protected function call($commandID, array $arguments = array())
    {
        $response = $this->client->executeCommand(
            $this->client->createCommand($commandID, $arguments)
        );

        if ($response instanceof ErrorResponseInterface) {
            throw new ServerException($response->getMessage());
        }

        return $response;
    }

    /**
     * Executes the specified Redis command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @throws AbortedMultiExecException
     * @throws CommunicationException
     *
     * @return $this|mixed
     */
    public function executeCommand(CommandInterface $command)
    {
        $this->initialize();

        if ($this->state->isCAS()) {
            return $this->client->executeCommand($command);
        }

        $response = $this->client->getConnection()->executeCommand($command);

        if ($response instanceof StatusResponse && $response == 'QUEUED') {
            $this->commands->enqueue($command);
        } elseif ($response instanceof ErrorResponseInterface) {
            throw new AbortedMultiExecException($this, $response->getMessage());
        } else {
            $this->onProtocolError('The server did not return a +QUEUED status response.');
        }

        return $this;
    }

    /**
     * Executes WATCH against one or more keys.
     *
     * @param string|array $keys One or more keys.
     *
     * @throws NotSupportedException
     * @throws ClientException
     *
     * @return mixed
     */
    public function watch($keys)
    {
        if (!$this->client->getProfile()->supportsCommand('WATCH')) {
            throw new NotSupportedException('WATCH is not supported by the current profile.');
        }

        if ($this->state->isWatchAllowed()) {
            throw new ClientException('Sending WATCH after MULTI is not allowed.');
        }

        $response = $this->call('WATCH', is_array($keys) ? $keys : array($keys));
        $this->state->flag(MultiExecState::WATCH);

        return $response;
    }

    /**
     * Finalizes the transaction by executing MULTI on the server.
     *
     * @return MultiExec
     */
    public function multi()
    {
        if ($this->state->check(MultiExecState::INITIALIZED | MultiExecState::CAS)) {
            $this->state->unflag(MultiExecState::CAS);
            $this->call('MULTI');
        } else {
            $this->initialize();
        }

        return $this;
    }

    /**
     * Executes UNWATCH.
     *
     * @throws NotSupportedException
     *
     * @return MultiExec
     */
    public function unwatch()
    {
        if (!$this->client->getProfile()->supportsCommand('UNWATCH')) {
            throw new NotSupportedException(
                'UNWATCH is not supported by the current profile.'
            );
        }

        $this->state->unflag(MultiExecState::WATCH);
        $this->__call('UNWATCH', array());

        return $this;
    }

    /**
     * Resets the transaction by UNWATCH-ing the keys that are being WATCHed and
     * DISCARD-ing pending commands that have been already sent to the server.
     *
     * @return MultiExec
     */
    public function discard()
    {
        if ($this->state->isInitialized()) {
            $this->call($this->state->isCAS() ? 'UNWATCH' : 'DISCARD');

            $this->reset();
            $this->state->flag(MultiExecState::DISCARDED);
        }

        return $this;
    }

    /**
     * Executes the whole transaction.
     *
     * @return mixed
     */
    public function exec()
    {
        return $this->execute();
    }

    /**
     * Checks the state of the transaction before execution.
     *
     * @param mixed $callable Callback for execution.
     *
     * @throws \InvalidArgumentException
     * @throws ClientException
     */
    private function checkBeforeExecution($callable)
    {
        if ($this->state->isExecuting()) {
            throw new ClientException(
                'Cannot invoke "execute" or "exec" inside an active transaction context.'
            );
        }

        if ($callable) {
            if (!is_callable($callable)) {
                throw new \InvalidArgumentException('The argument must be a callable object.');
            }

            if (!$this->commands->isEmpty()) {
                $this->discard();

                throw new ClientException(
                    'Cannot execute a transaction block after using fluent interface.'
                );
            }
        } elseif ($this->attempts) {
            $this->discard();

            throw new ClientException(
                'Automatic retries are supported only when a callable block is provided.'
            );
        }
    }

    /**
     * Handles the actual execution of the whole transaction.
     *
     * @param mixed $callable Optional callback for execution.
     *
     * @throws CommunicationException
     * @throws AbortedMultiExecException
     * @throws ServerException
     *
     * @return array
     */
    public function execute($callable = null)
    {
        $this->checkBeforeExecution($callable);

        $execResponse = null;
        $attempts = $this->attempts;

        do {
            if ($callable) {
                $this->executeTransactionBlock($callable);
            }

            if ($this->commands->isEmpty()) {
                if ($this->state->isWatching()) {
                    $this->discard();
                }

                return;
            }

            $execResponse = $this->call('EXEC');

            if ($execResponse === null) {
                if ($attempts === 0) {
                    throw new AbortedMultiExecException(
                        $this, 'The current transaction has been aborted by the server.'
                    );
                }

                $this->reset();

                continue;
            }

            break;
        } while ($attempts-- > 0);

        $response = array();
        $commands = $this->commands;
        $size = count($execResponse);

        if ($size !== count($commands)) {
            $this->onProtocolError('EXEC returned an unexpected number of response items.');
        }

        for ($i = 0; $i < $size; ++$i) {
            $cmdResponse = $execResponse[$i];

            if ($cmdResponse instanceof ErrorResponseInterface && $this->exceptions) {
                throw new ServerException($cmdResponse->getMessage());
            }

            $response[$i] = $commands->dequeue()->parseResponse($cmdResponse);
        }

        return $response;
    }

    /**
     * Passes the current transaction object to a callable block for execution.
     *
     * @param mixed $callable Callback.
     *
     * @throws CommunicationException
     * @throws ServerException
     */
    protected function executeTransactionBlock($callable)
    {
        $exception = null;
        $this->state->flag(MultiExecState::INSIDEBLOCK);

        try {
            call_user_func($callable, $this);
        } catch (CommunicationException $exception) {
            // NOOP
        } catch (ServerException $exception) {
            // NOOP
        } catch (\Exception $exception) {
            $this->discard();
        }

        $this->state->unflag(MultiExecState::INSIDEBLOCK);

        if ($exception) {
            throw $exception;
        }
    }

    /**
     * Helper method for protocol errors encountered inside the transaction.
     *
     * @param string $message Error message.
     */
    private function onProtocolError($message)
    {
        // Since a MULTI/EXEC block cannot be initialized when using aggregate
        // connections we can safely assume that Predis\Client::getConnection()
        // will return a Predis\Connection\NodeConnectionInterface instance.
        CommunicationException::handle(new ProtocolException(
            $this->client->getConnection(), $message
        ));
    }
}

/**
 * Exception class that identifies a MULTI / EXEC transaction aborted by Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class AbortedMultiExecException extends PredisException
{
    private $transaction;

    /**
     * @param MultiExec $transaction Transaction that generated the exception.
     * @param string    $message     Error message.
     * @param int       $code        Error code.
     */
    public function __construct(MultiExec $transaction, $message, $code = null)
    {
        parent::__construct($message, $code);
        $this->transaction = $transaction;
    }

    /**
     * Returns the transaction that generated the exception.
     *
     * @return MultiExec
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Session;

use Predis\ClientInterface;

/**
 * Session handler class that relies on Predis\Client to store PHP's sessions
 * data into one or multiple Redis servers.
 *
 * This class is mostly intended for PHP 5.4 but it can be used under PHP 5.3
 * provided that a polyfill for `SessionHandlerInterface` is defined by either
 * you or an external package such as `symfony/http-foundation`.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Handler implements \SessionHandlerInterface
{
    protected $client;
    protected $ttl;

    /**
     * @param ClientInterface $client  Fully initialized client instance.
     * @param array           $options Session handler options.
     */
    public function __construct(ClientInterface $client, array $options = array())
    {
        $this->client = $client;

        if (isset($options['gc_maxlifetime'])) {
            $this->ttl = (int) $options['gc_maxlifetime'];
        } else {
            $this->ttl = ini_get('session.gc_maxlifetime');
        }
    }

    /**
     * Registers this instance as the current session handler.
     */
    public function register()
    {
        if (PHP_VERSION_ID >= 50400) {
            session_set_save_handler($this, true);
        } else {
            session_set_save_handler(
                array($this, 'open'),
                array($this, 'close'),
                array($this, 'read'),
                array($this, 'write'),
                array($this, 'destroy'),
                array($this, 'gc')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open($save_path, $session_id)
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($session_id)
    {
        if ($data = $this->client->get($session_id)) {
            return $data;
        }

        return '';
    }
    /**
     * {@inheritdoc}
     */
    public function write($session_id, $session_data)
    {
        $this->client->setex($session_id, $this->ttl, $session_data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($session_id)
    {
        $this->client->del($session_id);

        return true;
    }

    /**
     * Returns the underlying client instance.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the session max lifetime value.
     *
     * @return int
     */
    public function getMaxLifeTime()
    {
        return $this->ttl;
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Monitor;

use Predis\ClientInterface;
use Predis\Connection\AggregateConnectionInterface;
use Predis\NotSupportedException;

/**
 * Redis MONITOR consumer.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Consumer implements \Iterator
{
    private $client;
    private $valid;
    private $position;

    /**
     * @param ClientInterface $client Client instance used by the consumer.
     */
    public function __construct(ClientInterface $client)
    {
        $this->assertClient($client);

        $this->client = $client;

        $this->start();
    }

    /**
     * Automatically stops the consumer when the garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->stop();
    }

    /**
     * Checks if the passed client instance satisfies the required conditions
     * needed to initialize a monitor consumer.
     *
     * @param ClientInterface $client Client instance used by the consumer.
     *
     * @throws NotSupportedException
     */
    private function assertClient(ClientInterface $client)
    {
        if ($client->getConnection() instanceof AggregateConnectionInterface) {
            throw new NotSupportedException(
                'Cannot initialize a monitor consumer over aggregate connections.'
            );
        }

        if ($client->getProfile()->supportsCommand('MONITOR') === false) {
            throw new NotSupportedException("The current profile does not support 'MONITOR'.");
        }
    }

    /**
     * Initializes the consumer and sends the MONITOR command to the server.
     */
    protected function start()
    {
        $this->client->executeCommand(
            $this->client->createCommand('MONITOR')
        );
        $this->valid = true;
    }

    /**
     * Stops the consumer. Internally this is done by disconnecting from server
     * since there is no way to terminate the stream initialized by MONITOR.
     */
    public function stop()
    {
        $this->client->disconnect();
        $this->valid = false;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        // NOOP
    }

    /**
     * Returns the last message payload retrieved from the server.
     *
     * @return Object
     */
    public function current()
    {
        return $this->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Checks if the the consumer is still in a valid state to continue.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->valid;
    }

    /**
     * Waits for a new message from the server generated by MONITOR and returns
     * it when available.
     *
     * @return Object
     */
    private function getValue()
    {
        $database = 0;
        $client = null;
        $event = $this->client->getConnection()->read();

        $callback = function ($matches) use (&$database, &$client) {
            if (2 === $count = count($matches)) {
                // Redis <= 2.4
                $database = (int) $matches[1];
            }

            if (4 === $count) {
                // Redis >= 2.6
                $database = (int) $matches[2];
                $client = $matches[3];
            }

            return ' ';
        };

        $event = preg_replace_callback('/ \(db (\d+)\) | \[(\d+) (.*?)\] /', $callback, $event, 1);
        @list($timestamp, $command, $arguments) = explode(' ', $event, 3);

        return (object) array(
            'timestamp' => (float) $timestamp,
            'database' => $database,
            'client' => $client,
            'command' => substr($command, 1, -1),
            'arguments' => $arguments,
        );
    }
}

/* --------------------------------------------------------------------------- */

namespace Predis\Replication;

use Predis\Command\CommandInterface;
use Predis\NotSupportedException;

/**
 * Defines a strategy for master/slave replication.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ReplicationStrategy
{
    protected $disallowed;
    protected $readonly;
    protected $readonlySHA1;

    /**
     *
     */
    public function __construct()
    {
        $this->disallowed = $this->getDisallowedOperations();
        $this->readonly = $this->getReadOnlyOperations();
        $this->readonlySHA1 = array();
    }

    /**
     * Returns if the specified command will perform a read-only operation
     * on Redis or not.
     *
     * @param CommandInterface $command Command instance.
     *
     * @throws NotSupportedException
     *
     * @return bool
     */
    public function isReadOperation(CommandInterface $command)
    {
        if (isset($this->disallowed[$id = $command->getId()])) {
            throw new NotSupportedException(
                "The command '$id' is not allowed in replication mode."
            );
        }

        if (isset($this->readonly[$id])) {
            if (true === $readonly = $this->readonly[$id]) {
                return true;
            }

            return call_user_func($readonly, $command);
        }

        if (($eval = $id === 'EVAL') || $id === 'EVALSHA') {
            $sha1 = $eval ? sha1($command->getArgument(0)) : $command->getArgument(0);

            if (isset($this->readonlySHA1[$sha1])) {
                if (true === $readonly = $this->readonlySHA1[$sha1]) {
                    return true;
                }

                return call_user_func($readonly, $command);
            }
        }

        return false;
    }

    /**
     * Returns if the specified command is not allowed for execution in a master
     * / slave replication context.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return bool
     */
    public function isDisallowedOperation(CommandInterface $command)
    {
        return isset($this->disallowed[$command->getId()]);
    }

    /**
     * Checks if a SORT command is a readable operation by parsing the arguments
     * array of the specified commad instance.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return bool
     */
    protected function isSortReadOnly(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        return ($c = count($arguments)) === 1 ? true : $arguments[$c - 2] !== 'STORE';
    }

    /**
     * Marks a command as a read-only operation.
     *
     * When the behavior of a command can be decided only at runtime depending
     * on its arguments, a callable object can be provided to dynamically check
     * if the specified command performs a read or a write operation.
     *
     * @param string $commandID Command ID.
     * @param mixed  $readonly  A boolean value or a callable object.
     */
    public function setCommandReadOnly($commandID, $readonly = true)
    {
        $commandID = strtoupper($commandID);

        if ($readonly) {
            $this->readonly[$commandID] = $readonly;
        } else {
            unset($this->readonly[$commandID]);
        }
    }

    /**
     * Marks a Lua script for EVAL and EVALSHA as a read-only operation. When
     * the behaviour of a script can be decided only at runtime depending on
     * its arguments, a callable object can be provided to dynamically check
     * if the passed instance of EVAL or EVALSHA performs write operations or
     * not.
     *
     * @param string $script   Body of the Lua script.
     * @param mixed  $readonly A boolean value or a callable object.
     */
    public function setScriptReadOnly($script, $readonly = true)
    {
        $sha1 = sha1($script);

        if ($readonly) {
            $this->readonlySHA1[$sha1] = $readonly;
        } else {
            unset($this->readonlySHA1[$sha1]);
        }
    }

    /**
     * Returns the default list of disallowed commands.
     *
     * @return array
     */
    protected function getDisallowedOperations()
    {
        return array(
            'SHUTDOWN' => true,
            'INFO' => true,
            'DBSIZE' => true,
            'LASTSAVE' => true,
            'CONFIG' => true,
            'MONITOR' => true,
            'SLAVEOF' => true,
            'SAVE' => true,
            'BGSAVE' => true,
            'BGREWRITEAOF' => true,
            'SLOWLOG' => true,
        );
    }

    /**
     * Returns the default list of commands performing read-only operations.
     *
     * @return array
     */
    protected function getReadOnlyOperations()
    {
        return array(
            'EXISTS' => true,
            'TYPE' => true,
            'KEYS' => true,
            'SCAN' => true,
            'RANDOMKEY' => true,
            'TTL' => true,
            'GET' => true,
            'MGET' => true,
            'SUBSTR' => true,
            'STRLEN' => true,
            'GETRANGE' => true,
            'GETBIT' => true,
            'LLEN' => true,
            'LRANGE' => true,
            'LINDEX' => true,
            'SCARD' => true,
            'SISMEMBER' => true,
            'SINTER' => true,
            'SUNION' => true,
            'SDIFF' => true,
            'SMEMBERS' => true,
            'SSCAN' => true,
            'SRANDMEMBER' => true,
            'ZRANGE' => true,
            'ZREVRANGE' => true,
            'ZRANGEBYSCORE' => true,
            'ZREVRANGEBYSCORE' => true,
            'ZCARD' => true,
            'ZSCORE' => true,
            'ZCOUNT' => true,
            'ZRANK' => true,
            'ZREVRANK' => true,
            'ZSCAN' => true,
            'ZLEXCOUNT' => true,
            'ZRANGEBYLEX' => true,
            'ZREVRANGEBYLEX' => true,
            'HGET' => true,
            'HMGET' => true,
            'HEXISTS' => true,
            'HLEN' => true,
            'HKEYS' => true,
            'HVALS' => true,
            'HGETALL' => true,
            'HSCAN' => true,
            'HSTRLEN' => true,
            'PING' => true,
            'AUTH' => true,
            'SELECT' => true,
            'ECHO' => true,
            'QUIT' => true,
            'OBJECT' => true,
            'BITCOUNT' => true,
            'BITPOS' => true,
            'TIME' => true,
            'PFCOUNT' => true,
            'SORT' => array($this, 'isSortReadOnly'),
        );
    }
}

/* --------------------------------------------------------------------------- */

