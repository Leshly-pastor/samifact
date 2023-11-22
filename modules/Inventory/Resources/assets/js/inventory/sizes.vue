<template>
    <el-dialog :title="titleDialog"
               width="40%"
               :visible="showDialog"
               @open="create"
               :close-on-click-modal="false"
               :close-on-press-escape="false"
               append-to-body
               :show-close="false">
        <div class="form-body">
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Talla</th>
                            <th>Cantidad</th>
                            <th>Stock real</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(row, index) in sizes" :key="index">
                          
                            <th>{{ row.size }}</th>
                            <th class>{{ row.stock }}</th>
                            <th>
                                <el-input-number v-model="row.qty" :min="0" :precision="0" :controls="false" size="mini" ></el-input-number>
                            </th>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="form-actions text-end pt-2">
            <el-button @click.prevent="close()">Cerrar</el-button>
            <el-button type="primary" @click="submit">Guardar</el-button>
        </div>
    </el-dialog>
</template>

<script>
export default {
    props: ["showDialog", "sizes", "stock", "recordId", "quantity"],
    data() {
        return {
            titleDialog: "Tallass",
            loading: false,
            errors: {},
            form: {},
            states: ["Activo", "Inactivo", "Desactivado", "Voz", "M2m"],
            idSelected: null
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
    methods: {
        changeSelect(index, id, quantity_lot) {

        
        },
        handleSelectionChange(val) {
            //this.$refs.multipleTable.clearSelection();
            let row = val[val.length - 1];
            this.multipleSelection = [row];
        },
        create() {
            console.log("🚀 ~ file: sizes.vue:76 ~ create ~ this.sizes:", this.sizes)
        },

        async submit() {
            let sizes = this.sizes
            console.log("🚀 ~ file: sizes.vue:80 ~ submit ~ sizes:", sizes)
            
            await this.$emit("addRowSelectSize", sizes);
            await this.$emit("update:showDialog", false);
        },

        clickCancel(item) {
            //this.lots.splice(index, 1);
            item.deleted = true;
            this.$emit("addRowLotGroup", this.lots);
        },

        async clickCancelSubmit() {
            this.$emit("addRowLotGroup", []);
            await this.$emit("update:showDialog", false);
        },
        close() {
            this.$emit("update:showDialog", false);
        }
    }
};
</script>
