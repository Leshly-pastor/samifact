<template>
    <div class="card">
        <div class="card-header">
            <h3 class="my-0">Tipo de cambio</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <label class="control-label">Moneda</label>
                    <el-select
                        v-model="form.currency_id"
                        placeholder="Seleccione una moneda"
                        @change="getData"
                        clearable
                    >
                        <el-option
                            v-for="item in currencies"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                        ></el-option>
                    </el-select>
                </div>
                <div class="col">
                    <label class="control-label">Fecha</label>
                    <el-date-picker
                        clearable
                        v-model="form.date"
                        type="date"
                        value-format="yyyy-MM-dd"
                        placeholder="Seleccione una fecha"
                        @change="getData"
                    ></el-date-picker>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <button
                        type="button"
                        class="btn btn-custom btn-sm mt-2 mr-2"
                        @click.prevent="clickCreate()"
                    >
                        <i class="fa fa-plus-circle"></i> Nuevo
                    </button>
                </div>
            </div>
            <div class="table-responsive" v-loading="loading">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Moneda</th>
                            <th>Fecha</th>
                            <th>Venta</th>
                            <th>Compra</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in records" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td>{{ row.currency }}</td>
                            <td>{{ row.date }}</td>
                            <td>{{ row.sale }}</td>
                            <td>{{ row.purchase }}</td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn waves-effect waves-light btn-sm btn-info"
                                    @click.prevent="clickCreate(row.id)"
                                >
                                    Editar
                                </button>
                                <!--<button type="button" class="btn waves-effect waves-light btn-sm btn-danger"  @click.prevent="clickDelete(row.id)">Eliminar</button>-->
                            </td>
                        </tr>
                    </tbody>
                </table>
                <el-pagination
                    @current-change="getData"
                    layout="total, prev, pager, next"
                    :total="pagination.total"
                    :current-page.sync="pagination.current_page"
                    :page-size="Number(pagination.per_page)"
                >
                </el-pagination>
            </div>
        </div>
        <currency-types-form
            :showDialog.sync="showDialog"
            :recordId="recordId"
            :currencies="currencies"
        ></currency-types-form>
    </div>
</template>

<script>
import CurrencyTypesForm from "./form.vue";
import { deletable } from "../../../mixins/deletable";
import queryString from "query-string";
export default {
    mixins: [deletable],
    props: ["typeUser"],
    components: { CurrencyTypesForm },
    data() {
        return {
            loading: false,
            pagination: {},
            currencies: [],
            showDialog: false,
            resource: "exchange_currency",
            recordId: null,
            records: [],
            form: {
                currency_id: null,
                date: null,
            },
        };
    },
    created() {
        this.$eventHub.$on("reloadData", () => {
            this.getData();
        });
        this.getData();
        this.getTables();
    },
    methods: {
        getQueryParameters() {
            return queryString.stringify({
                page: this.pagination.current_page,
                limit: this.limit,
                ...this.form,
            });
        },
        getTables() {
            this.$http.get(`/${this.resource}/tables`).then((response) => {
                this.currencies = response.data.currencies;
                this.currencies = this.currencies.filter(
                    (v, i, a) => a.findIndex((t) => t.id === v.id) === i
                );
            });
        },
        getData() {
            this.loading = true;
            this.$http
                .get(`/${this.resource}/records?${this.getQueryParameters()}`)
                .then((response) => {
                    this.records = response.data.data;
                    this.pagination = response.data.meta;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        clickCreate(recordId = null) {
            this.recordId = recordId;
            this.showDialog = true;
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
    },
};
</script>
