<template>
    <div>
        <div class="row">
            <div class="col-md-12 col-lg-12 col-xl-12">
                <div class="row" v-if="applyFilter">
                    <div class="col-lg-4 col-md-4 col-sm-12">
                            <label style="width: 100%">Filtrar por:</label>
                            <el-select
                                v-model="search.column"
                                placeholder="Select"
                                @change="changeClearInput"
                            >
                                <el-option
                                    v-for="(label, key) in columns"
                                    :key="key"
                                    :value="key"
                                    :label="label"
                                ></el-option>
                            </el-select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 ">
                        <template
                            v-if="
                                search.column == 'date_of_issue' ||
                                search.column == 'date_of_due' ||
                                search.column == 'date_of_payment' ||
                                search.column == 'delivery_date'
                            "
                        >
                        <br>
                            <el-date-picker
                                v-model="search.value"
                                type="date"
                                style="width: 100%"
                                placeholder="Buscar"
                                value-format="yyyy-MM-dd"
                                @change="getRecords"
                            >
                            </el-date-picker>
                        </template>
                        <template v-else-if="search.column == 'customer_id'">
                            <br>
                            <el-input
                                placeholder="Buscar"
                                v-model="search.value"
                                style="width: 100%"
                                prefix-icon="el-icon-search"
                                @input="getRecordsInput"
                            >
                               
                            </el-input>
                        </template>
                        <template v-else>
                            <br>
                            <el-input
                                placeholder="Buscar"
                                v-model="search.value"
                                style="width: 100%"
                                prefix-icon="el-icon-search"
                                @input="getRecordsInput"
                            >
                            </el-input>
                        </template>
                    </div>
                    <div
                        v-if="resource == 'finances/income'"
                        class="col-lg-3 col-md-4 col-sm-12 "
                    >
                        <template v-if="records.length > 0">
                            <br>
                            <el-button
                                class="submit"
                                type="success"
                                @click.prevent="clickDownload('excel')"
                                ><i class="fa fa-file-excel"></i> Exportal Excel
                            </el-button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <slot name="heading"></slot>
                        </thead>
                        <tbody>
                            <slot
                                v-for="(row, index) in records"
                                :row="row"
                                :index="customIndex(index)"
                            ></slot>
                        </tbody>
                    </table>
                    <div>
                        <el-pagination
                            @current-change="getRecords"
                            layout="total, prev, pager, next"
                            :total="pagination.total"
                            :current-page.sync="pagination.current_page"
                            :page-size="pagination.per_page"
                        >
                        </el-pagination>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import moment from "moment";
import queryString from "query-string";

export default {
    props: {
        resource: String,
        applyFilter: {
            type: Boolean,
            default: true,
            required: false,
        },
    },
    data() {
        return {
            customers: [],
            search: {
                column: null,
                value: null,
            },
            columns: [],
            records: [],
            pagination: {},
            loading: false,
            timer:null,
        };
    },
    computed: {},
    created() {
        this.$eventHub.$on("reloadData", () => {
            this.getRecords();
        });
    },
    async mounted() {
        // let column_resource = _.split(this.resource, '/')
        // console.log(column_resource)
        await this.$http.get(`/${this.resource}/columns`).then((response) => {
            this.columns = response.data;
            this.search.column = _.head(Object.keys(this.columns));
        });
        await this.getRecords();
    },
    methods: {
        getRecordsInput() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.getRecords();
            }, 500);
        },
        searchRemoteCustomers(input) {
            if (input.length > 0) {
                this.loading = true;
                let parameters = `input=${input}&document_type_id=&operation_type_id=`;

                this.$http
                    .get(`/documents/search/customers?${parameters}`)
                    .then((response) => {
                        this.customers = response.data.customers;
                    })
                    .catch((error) => this.axiosError(error))
                    .finally(() => (this.loading = false));
            }
        },
        clickDownload(type) {
            window.open(
                `/${
                    this.resource
                }/report/${type}/?${this.getQueryParameters()}`,
                "_blank"
            );
        },
        customIndex(index) {
            return (
                this.pagination.per_page * (this.pagination.current_page - 1) +
                index +
                1
            );
        },
        getRecords() {
            return this.$http
                .get(`/${this.resource}/records?${this.getQueryParameters()}`)
                .then((response) => {
                    this.records = response.data.data;
                    this.pagination = response.data.meta;
                    this.pagination.per_page = parseInt(
                        response.data.meta.per_page
                    );
                });
        },
        getQueryParameters() {
            return queryString.stringify({
                page: this.pagination.current_page,
                limit: this.limit,
                ...this.search,
            });
        },
        changeClearInput() {
            this.search.value = "";
            this.getRecords();
        },
    },
};
</script>
