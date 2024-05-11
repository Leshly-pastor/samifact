<template>
    <el-dialog
        :visible="showDialog"
        width="70%"
        center
        :title="title"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        @close="close"
        @open="open"
        v-loading="loading"
    >
        <div class="row">
            <div class="col-md-4">
                <label class="control-label">Fecha</label>
                <el-date-picker
                    v-model="form.date"
                    type="date"
                    value-format="yyyy-MM-dd"
                    placeholder="Seleccione una fecha"
                ></el-date-picker>
            </div>
            <div class="col-md-4">
                <label class="control-label">Venta</label>
                <el-input
                    v-model="form.sale"
                    type="number"
                    step="any"
                ></el-input>
            </div>
            <div class="col-md-4">
                <label class="control-label">Compra</label>
                <el-input
                    v-model="form.purchase"
                    type="number"
                    step="any"
                ></el-input>
            </div>
        </div>

        <span slot="footer" class="dialog-footer">
            <el-button @click="close">Cancelar</el-button>
            <el-button type="primary" @click="submit">Guardar</el-button>
        </span>
    </el-dialog>
</template>

<script>
export default {
    props: ["showDialog", "currency", "date"],
    data() {
        return {
            loading: false,
            form: {
                id: null,
                date: this.date,
                currency_id: this.currency.id,
                sale: 0,
                purchase: 0,
            },
            resource: "exchange_currency",
            title: "Tipo de cambio",
        };
    },
    methods: {
        async submit() {
            try {
                if (!this.form.currency_id) {
                    this.form.currency_id = this.currency.id;
                }
                this.loading = true;
                const response = await this.$http.post(
                    `/${this.resource}`,
                    this.form
                );
                if (response.status === 200) {
                    let data = response.data;
                    if (data.success) {
                        this.$message.success(data.message);
                        this.$emit("changeExchangeRate", true);
                        this.close();
                    } else {
                        this.$message.error(data.msg);
                    }
                }
            } catch (e) {
                this.$message.error("Error al guardar los datos");
            } finally {
                this.loading = false;
            }
        },
        initForm() {
            this.form = {
                id: null,
                date: this.date,
                sale: 0,
                purchase: 0,
                currency_id: this.currency.id,
            };
        },
        async getData() {
            try {
                this.loading = true;
                const response = await this.$http.get(
                    `/${this.resource}/${this.date}/${this.currency.id}`
                );
                if (response.status === 200) {
                    let data = response.data;
                    if (data.success) {
                        this.title = "Editar tipo de cambio " + this.currency.description;
                        this.form = data;
                    } else {
                        this.$message.success("Crear tipo de cambio");
                    }
                }
            } catch (e) {
                this.$message.error("Error al obtener los datos");
            } finally {
                this.loading = false;
            }
        },
        close() {
            this.$emit("update:showDialog", false);
        },
        open() {
            this.initForm();
            this.title = "Tipo de cambio " + this.currency.description;
            this.getData();
        },
    },
};
</script>
