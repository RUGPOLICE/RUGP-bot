@servers(['production' => 'gitlab@94.228.168.95'])

@setup
    $repository = 'git@gitlab.com:wanord/rugp.git';
    $releases_dir = '/var/www/rugp_user/data/www/rugp.app/releases';
    $app_dir = '/var/www/rugp_user/data/www/rugp.app';
    $release = date('YmdHis');
    $new_release_dir = "$releases_dir/$release";
@endsetup

@story('deploy')
    clone_repository
    run_composer
    run_node
    update_symlinks
    optimize
    restart_workers
    change_owner
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
    chown -R gitlab:rugp_user {{ $new_release_dir }}
    chmod -R g+w {{ $new_release_dir }}
    cd {{ $new_release_dir }}
    git reset --hard {{ $commit }}
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --prefer-dist --no-scripts -q -o
@endtask

@task('run_node')
    echo "Node install"
    cd {{ $new_release_dir }}/utils/scanner
    npm install
@endtask

@task('update_symlinks')
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage/ {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }}/ {{ $app_dir }}/stand
@endtask

@task('optimize')
    cd {{ $new_release_dir }}
    php artisan optimize
    php artisan storage:link
    php artisan migrate:auto --force
    php artisan nutgram:hook:set https://rugp.app/api/telegram
@endtask

@task('restart_workers')
    sudo supervisorctl restart rugp-production:*
@endtask

@task('change_owner')
    chown -R rugp_user:rugp_user {{ $new_release_dir }}
@endtask

