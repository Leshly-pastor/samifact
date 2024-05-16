<template>
    <el-dialog
        :title="titleDialog"
        width="40%"
        :visible="showDialog"
        @open="create"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        append-to-body
        :show-close="false"
    >
        <div class="form-body" v-if="lots">
            <div class="row">
                <div class="col-md-4">
                    <label class="control-label">Inicio de rango</label>
                    <el-input
                        v-model="start_range"
                        placeholder="Inicio del rango"
                    ></el-input>
                </div>
                <div class="col-md-4">
                    <label class="control-label">Fin de rango</label>
                    <el-input
                        v-model="end_range"
                        placeholder="Fin del rango"
                    ></el-input>
                </div>
                <div class="col-md-4">
                    <el-button
                        type="primary"
                        @click.prevent="addRange"
                        class="mt-4"
                    >
                        Ingresar
                    </el-button>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <table width="100%">
                        <thead>
                            <tr width="100%">
                                <th v-if="lots.length > 0">Serie</th>
                                <th v-if="lots.length > 0">Estado</th>
                                <th v-if="lots.length > 0">Fecha</th>
                                <th width="15%">
                                    <a
                                        href="#"
                                        @click.prevent="clickAddLot"
                                        class="text-center font-weight-bold text-info"
                                        >[+ Agregar]</a
                                    >
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, index) in paginatedLots"
                                :key="index"
                                width="100%"
                            >
                                <td>
                                    <div class="form-group mb-2 mr-2">
                                        <el-input
                                            @blur="
                                                duplicateSerie(
                                                    row.series,
                                                    index
                                                )
                                            "
                                            v-model="row.series"
                                        ></el-input>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group mb-2 mr-2">
                                        <el-select v-model="row.state">
                                            <el-option
                                                v-for="(
                                                    option, index
                                                ) in states"
                                                :key="index"
                                                :value="option"
                                                :label="option"
                                            ></el-option>
                                        </el-select>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group mb-2 mr-2">
                                        <el-date-picker
                                            v-model="row.date"
                                            type="date"
                                            value-format="yyyy-MM-dd"
                                            :clearable="false"
                                        ></el-date-picker>
                                    </div>
                                </td>
                                <td class="series-table-actions text-center">
                                    <button
                                        type="button"
                                        class="btn waves-effect waves-light btn-sm btn-danger"
                                        @click.prevent="clickCancel(index)"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                                <br />
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    <el-pagination
                                        @current-change="handleCurrentChange"
                                        layout="total, prev, pager, next"
                                        :total="lots.length"
                                        :current-page.sync="currentPage"
                                        :page-size="itemsPerPage"
                                    >
                                    </el-pagination>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="form-actions text-end pt-2">
            <el-button @click.prevent="clickCancelSubmit()">Cancelar</el-button>
            <el-button type="primary" @click="submit">Guardar</el-button>
        </div>
    </el-dialog>
</template>

<script>
export default {
    props: ["showDialog", "lots", "stock", "recordId"],
    data() {
        return {
            start_range: "",
            end_range: "",
            currentPage: 1,
            itemsPerPage: 20,
            titleDialog: "Series",
            loading: false,
            errors: {},
            form: {},
            states: ["Activo", "Inactivo", "Desactivado", "Voz", "M2m"],
        };
    },
    async created() {
        // await this.$http.get(`/pos/payment_tables`)
        //     .then(response => {
        //         this.payment_method_types = response.data.payment_method_types
        //         this.cards_brand = response.data.cards_brand
        //         this.clickAddLot()
        //     })
    },
    computed: {
        paginatedLots() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.lots.slice(start, end);
        },
    },
    methods: {
        addRange() {
            //verificar si el rango es valido
            if (this.start_range == "" || this.end_range == "") {
                return this.$message.error("Ingrese el rango de series");
            }
    
            if (this.start_range > this.end_range) {
                return this.$message.error("El rango de series no es valido");
            }
            let start = parseInt(this.start_range);
            let end = parseInt(this.end_range);
            let quantity = end - start + 1;

            if (quantity != this.stock) {
                return this.$message.error(
                    "La cantidad de series no coincide con el stock"
                );
            }
            let localLots = [];

            for (let i = 0; i < quantity; i++) {
                localLots.push({
                    id: null,
                    item_id: null,
                    series: start + i,
                    date: moment().format("YYYY-MM-DD"),
                    state: "Activo",
                });
            }
            this.$emit("update:lots", localLots);
            this.start_range = "";
            this.end_range = "";
            
            // this.paginatedLots = localLots;
            this.currentPage = 1;
            console.log("🚀 ~ addRange ~ lots:", this.lots)
        },
        handleCurrentChange(val) {
            this.currentPage = val;
        },
        async duplicateSerie(data, index) {
            let duplicates = await _.filter(this.lots, { series: data });
            if (duplicates.length > 1) {
                this.lots[index].series = "";
            }
        },
        create() {
            this.start_range = "";
            this.end_range = "";
            this.currentPage = 1;
            if (!this.recordId) {
                if (this.lots.length == 0) {
                    this.addMoreLots(this.stock);
                } else {
                    let quantity = this.stock - this.lots.length;
                    if (quantity > 0) {
                        this.addMoreLots(quantity);
                    }
                }
            }
        },
        addMoreLots(number) {
            for (let i = 0; i < number; i++) {
                this.clickAddLot();
            }
        },
        deleteMoreLots(number) {
            for (let i = 0; i < number; i++) {
                this.lots.pop();
                this.$emit("addRowLot", this.lots);
            }
        },
        async validateLots() {
            let error = 0;

            await this.lots.forEach((element) => {
                if (element.series == null) {
                    error++;
                }
            });

            if (error > 0)
                return {
                    success: false,
                    message: "El campo serie es obligatorio",
                };

            return { success: true };
        },
        async submit() {
            let val_lots = await this.validateLots();
            if (!val_lots.success) return this.$message.error(val_lots.message);

            await this.$emit("addRowLot", this.lots);
            await this.$emit("update:showDialog", false);
        },
        clickAddLot() {
            if (!this.recordId) {
                if (this.lots.length >= this.stock)
                    return this.$message.error(
                        "La cantidad de registros es superior al stock o cantidad"
                    );
            }

            this.lots.push({
                id: null,
                item_id: null,
                series: null,
                date: moment().format("YYYY-MM-DD"),
                state: "Activo",
            });

            this.$emit("addRowLot", this.lots);
        },

        close() {
            this.$emit("update:showDialog", false);
            this.$emit("addRowLot", this.lots);
        },
        clickCancel(index) {
            this.lots.splice(index, 1);
            // item.deleted = true
            this.$emit("addRowLot", this.lots);
        },

        async clickCancelSubmit() {
            this.$emit("addRowLot", []);
            await this.$emit("update:showDialog", false);
        },
        close() {
            this.$emit("update:showDialog", false);
        },
    },
};
</script>
