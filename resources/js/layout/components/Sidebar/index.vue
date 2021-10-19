<template>
  <div :class="{'has-logo':showLogo}">
    <logo v-if="showLogo" :collapse="isCollapse" />
    <el-scrollbar wrap-class="scrollbar-wrapper">
      <el-menu
        :show-timeout="200"
        :default-active="$route.path"
        :collapse="isCollapse"
        :background-color="variables.menuBg"
        :text-color="variables.menuText"
        :active-text-color="variables.menuActiveText"
        mode="vertical"
      ><template>
        <app-link :to="'/#/dashboard'" style="text-align:center;">
          <el-menu-item :index="'/#/dashboard'" class="el-menu-item submenu-title-noDropdown"> <i class="fa fa-dashboard" />Panel</el-menu-item>
        </app-link>
      </template>
      </el-menu>
    </el-scrollbar>
  </div>
</template>

<script>
import path from 'path';
import { isExternal } from '@/utils/validate';
import { mapGetters } from 'vuex';
import Logo from './Logo';
import variables from '@/styles/variables.scss';

export default {
  components: { Logo },
  computed: {
    ...mapGetters(['sidebar', 'permission_routers']),
    routes() {
      return this.$store.state.permission.routes;
    },
    showLogo() {
      return this.$store.state.settings.sidebarLogo;
    },
    variables() {
      return variables;
    },
    isCollapse() {
      return !this.sidebar.opened;
    },
    methods: {
      resolvePath(routePath) {
        if (this.isExternalLink(routePath)) {
          return routePath;
        }
        return path.resolve(this.basePath, routePath);
      },
      isExternalLink(routePath) {
        return isExternal(routePath);
      },
    },
  },
};
</script>
