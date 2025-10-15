<?php

class User {

    // GENERAL

    /**
     * @param array{user_id?: positive-int, phone?: string} $d
     *
     * @return array{id: int, plot_id: string, first_name: string, last_name: string, email: string, phone: string, access: positive-int}
     */
    public static function user_info(array $d): array {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? (int) $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        $default = [
            'id' => 0,
            'plot_id' => '',
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'access' => 0
        ];
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else  $where = false;
        // info
        if ($where) {
            $q = DB::query(
                "SELECT user_id id, plot_id, first_name, last_name, email, phone, access
                FROM users
                WHERE " . $where . " LIMIT 1"
            ) or die (DB::error());

            if ($row = DB::fetch_row($q)) {
                return $row;
            }
        }

        return $default;
    }

    /**
     * @param array{'search': string, 'offset': positive-int}|null $d
     *
     * @return array{'items': array-key, 'paginator': array-key}
     */
    public static function users_list(?array $d = []): array {
        // vars
        $search = get_str_array_key($d, 'search');
        $offset = get_int_array_key($d, 'offset');
        $limit = 20;
        $items = [];
        // where
        $where = [];
        if ($search) $where[] = "number LIKE '%".$search."%'";
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, phone, email, last_login
            FROM users ".$where." ORDER BY user_id LIMIT ".$offset.", ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'last_login' => date('Y/m/d', $row['last_login'])
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?';
        if ($search) $url .= '&search='.$search;
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

    // ACTIONS

    /**
     * @param array{'user-id': positive-int}|null $d
     *
     * @return array{'html': false|string|void}
     */
    public static function user_edit_window(?array $d = []): array {
        $user_id = get_int_array_key($d, 'user_id');
        HTML::assign('user', User::user_info(['user_id' => $user_id]));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    /**
     * @param array{
     *     'user_id': positive-int,
     *     'plot_id': positive-int,
     *     'first_name': string,
     *     'last_name': string,
     *     'email': string,
     *     'phone': string,
     *     'plots': string
     * }|null $d
     *
     * @return array
     */
    public static function user_edit_update(?array $d = []): array {
        // vars
        $user_id = get_int_array_key($d, 'user_id');
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        $plots = array_unique(array_filter(extract_int_list(get_str_array_key($d, 'plot_id'))));
        asort($plots, SORT_NUMERIC);
        $plots = implode(',', $plots);
        $first_name = get_str_array_key($d, 'first_name');
        $last_name = get_str_array_key($d, 'last_name');
        $email = strtolower(get_str_array_key($d, 'email'));
        $phone = preg_replace('~\D+~', '', get_str_array_key($d, 'phone'));

        if (!($first_name && $last_name && $email && $phone)) return Plot::plots_fetch(['offset' => $offset]);

        $stmt = DB::prepare(
            $user_id ?
                'UPDATE users SET
                    plot_id = :plot_id,
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone
                WHERE user_id = :user_id'
            : 'INSERT INTO users (plot_id, first_name, last_name, email, phone)
                VALUES (:plot_id, :first_name, :last_name, :email, :phone)') or die (DB::error());

        $user_id && $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':plot_id', $plots);
        $stmt->bindValue(':first_name', get_str_array_key($d, 'first_name'));
        $stmt->bindValue(':last_name', get_str_array_key($d, 'last_name'));
        $stmt->bindValue(':email', strtolower(get_str_array_key($d, 'email')));
        $stmt->bindValue(':phone', preg_replace('~\D+~', '', get_str_array_key($d, 'phone')));

        $stmt->execute();

        // output
        return Plot::plots_fetch(['offset' => $offset]);
    }
}
