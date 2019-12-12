@servers(['localhost' => '127.0.0.1'])

@setup
    $now = new DateTime();

    $repo = 'git@gitlab.4px.ru:4px/autoxml-img.git';

    if ($branch === 'master') {
        $dir = '/home/admin/web/cdn.autoxml.4px.tech/public_html';
        $supervisor_group = 'autoxml:*';
    }

    $dir_all = $dir . '/releases';
    $dir_current = $dir . '/current';
    $dir_active = $dir_all . '/' . $now->format('YmdHis');
@endsetup

@task('clone')
    echo '**************************************'
    echo '*             clone repo             *'
    echo '**************************************'

    [ -d {{ $dir_all }} ] || mkdir {{ $dir_all }}
    [ -d {{ $dir_active }} ] || mkdir {{ $dir_active }}

    git clone {{ $repo }} --branch {{ $branch }} --single-branch --depth 7 {{ $dir_active }}
    cd {{ $dir_active }}
    git checkout {{ $commit }}
@endtask

@task('composer')
    echo '**************************************'
    echo '*            composer run            *'
    echo '**************************************'

    cd {{ $dir_active }}
    composer install --prefer-dist --no-scripts -q -o
@endtask

@task('npm_install')
    echo '**************************************'
    echo '*             npm install            *'
    echo '**************************************'

    cd {{ $dir_active }}
    npm install
@endtask

@task('npm_run')
    echo '**************************************'
    echo '*               npm run              *'
    echo '**************************************'

    cd {{ $dir_active }}
    npm run production
@endtask

@task ('env_symlink')
    echo '**************************************'
    echo '*         enviroment symlink         *'
    echo '**************************************'

    echo 'linking .env file'
    ln -nfs {{ $dir }}/.env {{ $dir_active }}/.env
@endtask

@task('migrate')
    echo '**************************************'
    echo '*         database migration         *'
    echo '**************************************'

    cd {{ $dir_active }}
    php artisan migrate --force
@endtask

@task('config_cache')
    echo '**************************************'
    echo '*            cache config            *'
    echo '**************************************'

    cd {{ $dir_active }}
    php artisan config:cache
@endtask

@task('symlink')
    echo '**************************************'
    echo '*           create symlink           *'
    echo '**************************************'

    echo "linking image directory"
    rm -rf {{ $dir_active }}/public/image
    ln -nfs {{ $dir }}/public/image {{ $dir_active }}/public/image

    echo "linking image_blocked directory"
    rm -rf {{ $dir_active }}/public/image_blocked
    ln -nfs {{ $dir }}/public/image_blocked {{ $dir_active }}/public/image_blocked

    echo "linking record directory"
    rm -rf {{ $dir_active }}/public/record
    ln -nfs {{ $dir }}/public/record {{ $dir_active }}/public/record

    echo 'linking current release'
    ln -nfs {{ $dir_active }} {{ $dir_current }}
@endtask

@task ('remove_dir')
    echo '**************************************'
    echo '*             remove dir             *'
    echo '**************************************'

    @if (is_dir($dir_all))
        @php
            $list_dir = array_diff(scandir($dir_all), ['.', '..']);
            rsort($list_dir);
            $number_dir = 0;
        @endphp

        @foreach ($list_dir as $dir_name)
            @php
                if (!is_dir($dir_all . '/' . $dir_name)) {
                    continue;
                }
            @endphp

            @if ($number_dir > 1)
                rm -fr {{ $dir_all . '/' . $dir_name }}
            @endif

            @php
                $number_dir++;
            @endphp
        @endforeach
    @endif
@endtask

@task ('restart_queue')
    echo '**************************************'
    echo '*            restart queue           *'
    echo '**************************************'

    cd {{ $dir_current }}
    php artisan queue:restart
@endtask

@task ('restart_horizon')
    echo '**************************************'
    echo '*            restart horizon         *'
    echo '**************************************'

    cd {{ $dir_current }}
    php artisan horizon
@endtask

@macro('deploy')
    clone
    composer
    npm_install
    npm_run
    env_symlink
    migrate
    config_cache
    symlink
    remove_dir
    restart_queue
    restart_horizon
@endmacro
