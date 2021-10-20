
<template>
  <div class="app-container">
    <template>
      <h2>Métricas Generales</h2>
    </template>
    <br>
    <el-row :gutter="40" class="panel-group">
      <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col">
        <div class="card-panel">
          <div class="card-panel-icon-wrapper icon-people">
            <i class="el-icon-view" />
          </div>
          <div class="card-panel-description">
            <div class="card-panel-text">
              Páginas generales
            </div>
            Visitas: <count-to :start-val="0" :end-val="metrics.total_views" :duration="2600" class="" />
            Sesiones: <count-to :start-val="0" :end-val="metrics.total_sessions" :duration="2600" class="" />
          </div>
        </div>
      </el-col>
      <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col">
        <div class="card-panel">
          <div class="card-panel-icon-wrapper icon-shopping">
            <svg-icon icon-class="shopping" class-name="card-panel-icon" />
          </div>
          <div class="card-panel-description">
            <div class="card-panel-text">
              Páginas de productos
            </div>
            Visitas: <count-to :start-val="0" :end-val="metrics.total_product_views" :duration="3000" class="" />
            Sesiones: <count-to :start-val="0" :end-val="metrics.total_product_sessions" :duration="3000" class="" />
          </div>
        </div>
      </el-col>
      <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col">
        <div class="card-panel">
          <div class="card-panel-icon-wrapper icon-money">
            <i class="el-icon-sold-out" />
          </div>
          <div class="card-panel-description">
            <div class="card-panel-text">Productos sin stock
            </div>
            Visitas: <count-to :start-val="0" :end-val="metrics.total_views_outs" :duration="3200" class="" />
            Sesiones: <count-to :start-val="0" :end-val="metrics.total_sessions_outs" :duration="3200" class="" />
          </div>
        </div>
      </el-col>
      <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col">
        <div class="card-panel">
          <div class="card-panel-icon-wrapper icon-shopping">
            <i class="el-icon-check" />
          </div>
          <div class="card-panel-description">
            <div class="card-panel-text">
              Productos
            </div>
            En stock: <count-to :start-val="0" :end-val="metrics.avg_products_stock" :duration="3200" class="" />({{ metrics.avg_percent_product_stock }}%)<br>
            Sin Stock: <count-to :start-val="0" :end-val="metrics.avg_products_out_stock" :duration="3200" class="" />({{ metrics.avg_percent_product_outs }}%)
          </div>
        </div>
      </el-col>
    </el-row>
    <div class="filter-container">
      <el-button class="filter-item" style="margin-left: 10px;" type="primary" icon="el-icon-edit" @click="dialogFormVisible=true">
        {{ $t('table.pickdate') }}
      </el-button>
      <el-button v-waves :loading="downloadLoading" class="filter-item" type="primary" icon="el-icon-download" @click="handleDownload">
        {{ $t('table.export') }}
      </el-button>
    </div>

    <template>
      <h2>Productos en stock <span v-if="date"> {{ date }}</span></h2>
    </template>
    <br>
    <el-table
      :key="tableKey"
      v-loading="listLoading"
      :data="list"
      border
      fit
      highlight-current-row
      style="width: 100%;"
      @sort-change="sortChange"
    >
      <el-table-column :label="$t('table.id')" prop="id" sortable="custom" align="center" width="100px">
        <template slot-scope="scope">
          <span>{{ scope.row.id }}</span>
        </template>
      </el-table-column>
      <el-table-column :label="$t('table.productUrl')" width="650px" align="center">
        <template slot-scope="scope">
          <a target="_blank" :href="scope.row.product_url"><span>{{ scope.row.product_url }}</span></a>
        </template>
      </el-table-column>
      <el-table-column :label="$t('table.views')" prop="views" sortable="custom" width="150px" align="center">
        <template slot-scope="scope">
          <span>{{ scope.row.views }}</span>
        </template>
      </el-table-column>

      <el-table-column :label="$t('table.sessions')" prop="sessions" sortable="custom" width="150px" align="center">
        <template slot-scope="scope">
          <span>{{ scope.row.sessions }}</span>
        </template>
      </el-table-column>
      <el-table-column :label="$t('table.outStockDays')" align="center" width="250px" class-name="small-padding fixed-width">
        <template slot-scope="scope">
          <el-button type="primary" size="large" @click="fetchHistory(scope.row.product_url)">
            {{ $t('table.history') }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <pagination v-show="total>0" :total="total" :page.sync="listQuery.page" :limit.sync="listQuery.limit" @pagination="paginate" />

    <el-dialog :title="'Seleccionar Fecha'" :visible.sync="dialogFormVisible">
      <el-form ref="dataForm" :model="listQuery" label-position="left" label-width="170px" style="width: 100%; margin-left:50px;">
        <el-form-item :label="$t('table.date_start')" prop="timestamp">
          <el-date-picker v-model="listQuery.date_start" type="date" placeholder="Selecciona la fecha de incio" />
        </el-form-item>
        <el-form-item :label="$t('table.date_end')" prop="timestamp">
          <el-date-picker v-model="listQuery.date_end" type="date" placeholder="Seleccione la fecha final" />
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">
          {{ $t('table.cancel') }}
        </el-button>
        <el-button type="primary" @click="updateTable()">
          {{ $t('table.confirm') }}
        </el-button>
      </div>
    </el-dialog>

    <el-dialog :title="'Historial'" :visible.sync="dialogHistoryVisible">
      <el-table
        :key="tableKeyHistory"
        v-loading="historyLoading"
        :data="history"
        border
        fit
        highlight-current-row
        style="width: 100%;"
        @sort-change="sortChange"
      >
        <el-table-column :label="$t('table.id')" prop="id" sortable="custom" align="center" width="100px">
          <template slot-scope="scope">
            <span>{{ scope.row.id }}</span>
          </template>
        </el-table-column>
        <el-table-column :label="$t('table.productUrl')" width="350px" align="center">
          <template slot-scope="scope">
            <a target="_blank" :href="scope.row.product_url"><span>{{ scope.row.product_url }}</span></a>
          </template>
        </el-table-column>
        <el-table-column :label="$t('table.stock')" prop="stock" sortable="custom" width="150px" align="center">
          <template slot-scope="scope">
            <span>{{ scope.row.stock }}</span>
          </template>
        </el-table-column>

        <el-table-column :label="$t('table.date')" prop="date" sortable="custom" width="150px" align="center">
          <template slot-scope="scope">
            <span>{{ scope.row.date }}</span>
          </template>
        </el-table-column>
      </el-table>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogHistoryVisible = false">
          cerrar
        </el-button>

      </div>
    </el-dialog>
  </div>
</template>

<script>
import { fetchListInStock, fetchListByDateInStock, fetchProductHistory, paginateInStock, fetchFullListByDate } from '@/api/products';
import CountTo from 'vue-count-to';
import waves from '@/directive/waves'; // Waves directive
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination

export default {
  name: 'DashboardInStock',
  components: { Pagination, CountTo },
  directives: { waves },
  filters: {
    statusFilter(status) {
      const statusMap = {
        published: 'success',
        draft: 'info',
        deleted: 'danger',
      };
      return statusMap[status];
    },
  },
  data() {
    return {
      tableKey: 0,
      tableKeyHistory: 1,
      list: null,
      download: null,
      metrics: null,
      date: null,
      total: 0,
      listLoading: true,
      history: null,
      historyLoading: true,
      listQuery: {
        page: 1,
        limit: 20,
        importance: undefined,
        title: undefined,
        type: undefined,
        sort: '+id',
        date_start: new Date().setDate(new Date().getDate() - 1),
        date_end: new Date().setDate(new Date().getDate() - 1),
        date_start_string: '',
        date_end_string: '',
      },
      products: {
        date_start: '',
        date_end: '',
      },
      dialogFormVisible: false,
      dialogHistoryVisible: false,
      rules: {
        type: [{ required: true, message: 'type is required', trigger: 'change' }],
        timestamp: [{ type: 'date', required: true, message: 'timestamp is required', trigger: 'change' }],
        title: [{ required: true, message: 'title is required', trigger: 'blur' }],
      },
      downloadLoading: false,
    };
  },
  created() {
    this.getList();
  },
  methods: {
    async getList() {
      this.listLoading = true;
      const { data } = await fetchListInStock(this.listQuery);
      this.list = data.items;
      this.total = data.total;
      this.metrics = data.metrics;
      this.date = data.date;
      this.listQuery.date_start_string = data.date_start;
      this.listQuery.date_end_string = data.date_end;

      // Just to simulate the time of the request
      this.listLoading = false;
      this.getFullList();
    },
    async paginate() {
      this.listLoading = true;
      const { data } = await paginateInStock(JSON.stringify(this.listQuery));
      this.list = data.list;
      // Just to simulate the time of the request
      this.listLoading = false;
    },

    formatDate(date){
      var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

      if (month.length < 2){
        month = '0' + month;
      }

      if (day.length < 2){
        day = '0' + day;
      }

      return [year, month, day].join('-');
    },
    handleFilter() {
      this.listQuery.page = 1;
      this.getList();
    },
    sortChange(data) {
      const { prop, order } = data;
      if (prop === 'id') {
        this.sortByID(order);
      }
    },
    sortByID(order) {
      if (order === 'ascending') {
        this.listQuery.sort = '+id';
      } else {
        this.listQuery.sort = '-id';
      }
      this.handleFilter();
    },
    async updateTable(){
      this.listLoading = true;

      var date_s = this.formatDate(this.listQuery.date_start);
      var date_e = this.formatDate(this.listQuery.date_end);

      const { data } = await fetchListByDateInStock(date_s, date_e);

      this.list = data.items;
      this.total = data.total;
      this.metrics = data.metrics;
      this.date = data.date;
      this.listQuery.date_start_string = data.date_start;
      this.listQuery.date_end_string = data.date_end;

      // Just to simulate the time of the request
      this.listLoading = false;
      this.dialogFormVisible = false;
      this.getFullList();
    },
    async getFullList(){
      var date_s = this.formatDate(this.listQuery.date_start);
      var date_e = this.formatDate(this.listQuery.date_end);
      const { data } = await fetchFullListByDate(date_s, date_e, 0);
      this.download = data.list;
    },
    async fetchHistory(url){
      this.history = null;
      this.dialogHistoryVisible = true;
      this.historyLoading = true;
      const { data } = await fetchProductHistory(url);
      this.history = data.history;
      this.$notify({
        title: 'Todo bien',
        message: 'Historial cargado correctamente',
        type: 'success',
        duration: 2000,
      });

      // Just to simulate the time of the request
      this.historyLoading = false;
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const tHeader = ['Vistas', 'Sesiones', 'Url del producto'];
        const filterVal = ['views', 'sessions', 'product_url'];
        const data = this.formatJson(filterVal, this.download);
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'Listado-productos-en-stock-' + this.listQuery.date_start_string + '_' + this.listQuery.date_end_string,
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v => filterVal.map(j => {
        return v[j];
      }));
    },
  },
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
.panel-group {
  margin-top: 18px;
  .card-panel-col{
    margin-bottom: 32px;
  }
  .card-panel {
    height: 125px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    position: relative;
    overflow: hidden;
    color: #666;
    background: #fff;
    box-shadow: 4px 4px 40px rgba(0, 0, 0, .05);
    border-color: rgba(0, 0, 0, .05);
    &:hover {

    }
    .icon-people {
      color: #40c9c6;
    }
    .icon-message {
      color: #36a3f7;
    }
    .icon-money {
      color: #f4516c;
    }
    .icon-shopping {
      color: #34bfa3
    }
    .card-panel-icon-wrapper {
      float: left;
      margin: 14px 0 0 14px;
      padding: 16px;
      transition: all 0.38s ease-out;
      border-radius: 6px;
    }

    .card-panel-icon-wrapper i{
      font-size: 50px;
    }
    .card-panel-icon {
      float: left;
      font-size: 48px;
    }
    .card-panel-description {
      float: right;
      font-weight: bold;
      margin: 26px;
      margin-left: 0px;
      .card-panel-text {
        line-height: 18px;
        color: rgba(0, 0, 0, 0.45);
        font-size: 16px;
        margin-bottom: 12px;
      }
      .card-panel-num {
        font-size: 20px;
      }
    }
  }
}

.el-table{
  display:inline !important;
}

.el-table--group, .el-table--border {
    border: 0px solid #dfe6ec !important;
}
</style>

