<template>
    <div class="content" data-simplebar>
        <div class="header">
            <div class="text">
                <h1>{{LANG.manager.apps_list}}</h1>
            </div>
        </div>
        <div class="page">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <span class="nav-link"
                          @click="loadApps('installed')"
                          :class="activeTab === 'installed'? 'active' : ''">{{LANG.manager.installed_apps}}</span>
                </li>
                <li class="nav-item">
                    <span class="nav-link"
                          @click="loadApps('systems')"
                          :class="activeTab === 'systems'? 'active' : ''">{{LANG.manager.systems_apps}}</span>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active">
                    <div class="apps" v-if="!_isEmptyObj(apps) && !isLoading">
                        <div class="app-item" v-for="(app,index) in apps">
                            <div class="icon" @click="showDetailsApp(app)" >
                                <img :src="app.icon" :alt="app.name">
                                <div class="text">
                                    <h2 class="name">{{app.name}}</h2>
                                    <h3 class="info">{{LANG.manager.developer}}: {{app.developer}}</h3>
                                    <h3 class="info">{{LANG.manager.version}}: {{app.version}}</h3>
                                </div>
                            </div>
                            <div class="action" v-if="!app.sys_app">
                                      <span @click="removeApp(app)" class="btn"> <i
                                              class="fa fa-trash"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="pin-spinner" v-else-if="isLoading">
                    </div>
                    <div class="empty" v-else>
                        <div>{{LANG.setting.appManager.empty_app}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapMutations} from 'vuex';

    export default {
        data() {
            return {
                isLoading: false,
                activeTab: 'installed',
                apps: []
            }
        },
        methods: {
            ...mapMutations(['getApps']),
            loadApps(activeTab) {
                this.isLoading = true;
                if (activeTab != null)
                    this.activeTab = activeTab;
                this.$http.get(this.URL.API + 'app/get/' + this.activeTab).then((json) => {
                    this.isLoading = false;
                    this.apps = json.data
                });
            },
            showDetailsApp(app) {
                if(app.sys_app) return;

                this.$parent.selectedApp = app;
                this.$router.push({name: 'app-details', params: {package_name: app.package_name}});
            },
            removeApp(app) {
                this._notify(this.LANG.manager.alert, this.LANG.manager.are_you_sure_delete_app, null, [
                    {
                        text: this.LANG.manager.do_delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'app/remove/' + app.package_name).then((json) => {
                                this._loading = false;
                                app.state = 'download';
                                this.$delete(this.$store.state.apps, app.package_name);
                                this.$delete(this.apps, app.package_name);
                            });
                        }
                    },
                    {
                        text: this.LANG.manager.no,
                        func: () => {
                        }
                    }
                ]);
            },
        },
        created() {
            this.loadApps();
        }
    }
</script>
