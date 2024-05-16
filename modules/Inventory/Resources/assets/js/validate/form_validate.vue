<template>
    <el-dialog
        width="70%"
        @close="close"
        :visible="showDialog"
        @open="open"
        :title="title"
    >
        <div class="row" v-if="!searchBySeries">
            <div class="col-md-6 col-lg-6 col-12">
                <el-select
                    id="select-width"
                    ref="selectBarcode"
                    slot="prepend"
                    v-model="form.item_id"
                    :loading="loading_search"
                    :remote-method="searchRemoteItems"
                    filterable
                    placeholder="Buscar producto"
                    popper-class="el-select-items"
                    remote
                    value-key="id"
                    @change="changeItem"
                >
                    <el-option
                        v-for="option in items"
                        :key="option.id"
                        :label="option.full_description"
                        :value="option.id"
                    ></el-option>
                </el-select>
            </div>
            <div class="col-md-3 col-lg-3 col-12">
                <el-input
                    v-model="form.quantity"
                    :disabled="!form.item_id"
                ></el-input>
            </div>
            <div class="col-md-3 col-lg-3 col-12">
                <el-button type="primary" @click="insertProduct">
                    <i class="fas fa-plus"></i>
                </el-button>
            </div>
        </div>
        <div class="row" v-else>
            <div class="col-12">
                <el-select
                    id="select-width"
                    ref="selectBarcode"
                    slot="prepend"
                    v-model="form.series"
                    :loading="loading_search"
                    :remote-method="searchRemoteSeries"
                    filterable
                    placeholder="Buscar producto"
                    popper-class="el-select-items"
                    remote
                    value-key="id"
                >
                    <el-option
                        v-for="option in items"
                        :key="option.id"
                        :label="option.full_description"
                        :value="option.id"
                    ></el-option>
                </el-select>
            </div>
        </div>
        <div class="mt-1 d-flex justify-content-start">
            <div class="col-md-3 col-lg-3 col-12">
                <el-checkbox v-model="searchBySeries"
                    >Buscar producto seriado</el-checkbox
                >
            </div>
        </div>

        <div class="row mt-2">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in itemsSelected" :key="idx">
                            <td>{{ item.full_description }}</td>
                            <td>{{ item.quantity }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </el-dialog>
</template>

<script>
export default {
    props: ["warehouse_id", "showDialog", "warehouse"],
    data() {
        return {
            resource: "inventory/validate",
            searchBySeries: false,
            input: "",
            timer: null,
            title: "Validar inventario",
            quantity: 0,
            items: [],
            itemsSelected: [],
            loading_search: false,
            form: {
                item_id: null,
                quantity: 0,
                series: "",
            },
        };
    },
    computed: {
        placeholder() {
            return this.searchBySeries
                ? "Ingrese la serie del producto"
                : "Ingrese el nombre / codigo del producto";
        },
    },
    methods: {
        initForm() {
            this.form = {
                item_id: null,
                quantity: 0,
                series: "",
            };
        },
        changeItem() {},
        insertSeries() {
            let { item_id } = this.form;

            if (item_id) {
                let exists = this.itemsSelected.find(
                    (item) => item.id === item_id
                );
                if (exists) {
                    let itemSelected = this.itemsSelected.find(
                        (item) => item.id === item_id
                    );
                    itemSelected.lots.push(this.form.series);
                    itemSelected.quantity += 1;
                } else {
                    let item = this.items.find((item) => item.id === item_id);
                    let itemSelected = {
                        id: item.id,
                        full_description: item.full_description,
                        quantity: 1,
                        lots: [this.form.series],
                    };
                    this.itemsSelected.push(itemSelected);
                }
                this.initForm();
            } else {
                this.$message.error("Debe ingresar una serie");
            }
        },
        insertProduct() {
            let { item_id, quantity } = this.form;
            if (item_id && quantity) {
                let item = this.items.find((item) => item.id === item_id);
                let itemSelected = {
                    id: item.id,
                    full_description: item.full_description,
                    quantity: quantity,
                };
                this.itemsSelected.push(itemSelected);
                this.initForm();
            } else {
                this.$message.error(
                    "Debe seleccionar un producto y una cantidad"
                );
            }
        },
        open() {
            let { description } = this.warehouse;
            this.title = `Validar inventario del ${description}`;
        },
        close() {
            this.$emit("udpate:showDialog", false);
        },
        debounceSearch() {
            // this.$emit("debounceSearch", this.input);
        },
        async searchRemoteSeries(input) {
            if (input.length > 2) {
                this.loading_search = true;
                const params = {
                    input: input,
                    warehouse_id: this.warehouse_id,
                };
                await this.$http
                    .get(`/${this.resource}/search-series-validate/`, {
                        params,
                    })
                    .then((response) => {
                        let items = response.data.items;
                        if (items.length > 0) {
                            if (items.length !== 1) {
                                this.$message.error(
                                    "Se encontraron varios resultados"
                                );
                            } else {
                                this.items = items;
                                this.form.item_id = items[0].id;
                                this.insertSeries();
                            }
                        } else {
                            this.$message.error("No se encontraron resultados");
                        }
                        this.loading_search = false;
                    });
            }
        },
        async searchRemoteItems(input) {
            if (input.length > 2) {
                this.loading_search = true;
                const params = {
                    input: input,
                    warehouse_id: this.warehouse_id,
                };
                await this.$http
                    .get(`/${this.resource}/search-items-validate/`, { params })
                    .then((response) => {
                        console.log("🚀 ~ .then ~ response:", response);
                        this.items = response.data.items;
                        this.loading_search = false;
                    });
            }
        },
    },
};
</script>
