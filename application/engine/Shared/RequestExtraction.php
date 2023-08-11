<?php
/**
 * Query Factory
 * @author Andri Nowhere <andri.dot.py2018@gmail.com>
 */

namespace Andri\Engine\Shared;

class RequestExtraction {

    public static function default(array $options, array $default = null) {
        $query = [
            'perPage'       => 10,
            'page'          => 0,
            'sorted'        => 'created_at.desc',
        ];

        if (empty($options)) return $query;
        
        if (self::check('q', $options))           $query['q'] = $options['q'];
        if (self::check('per_page', $options))    $query['perPage'] = (int)$options['per_page'];
        if (self::check('sorted', $options))      $query['sorted'] = $options['sorted'];
       
        
        if (self::check('show', $options)) {
            unset($query['deleted_at']);
            $query['show'] = 'all';
        }    

        if (self::check('page', $options)) {
            $page = (int)$options['page'];
            $page = $page - 1;
            $page = ($page < 0) ? 0 : $page;
            $query['page'] = $page;
        }

        return $query;
    }


    public static function check($key, $options) {
        return (key_exists($key, $options) && (strlen("{$options[$key]}") > 0));
    }

    
    
    /**
     * Reformat Sorting
     * 
     * @param array $options
     */
    public static function sorted(array $options) {
        $sorted = 'created_at';
        $direct = 'desc';

        if (RequestExtraction::check('sorted_by', $options)) {
            $sorted = $options['sorted_by'];
        }

        if (RequestExtraction::check('sorted', $options)) {
            $direct = $options['sorted'] === "0" ? 'asc' : 'desc';
        }

        return $sorted .".". $direct;
    }
   
}