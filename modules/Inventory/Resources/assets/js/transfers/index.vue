<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>{{ title }}</span>
                </li>
            </ol>
            <div class="right-wrapper pull-right">
                <a
                    class="btn btn-custom btn-sm mt-2 mr-2"
                    href="#"
                    @click.prevent="clickCreate()"
                >
                    <i class="fa fa-plus-circle"></i> Nuevo
                </a>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header">
                <h3 class="my-0">Listado de {{ title }}</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading">
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Almacen Inicial</th>

                        <th>Almacen Destino</th>
                        <th>Detalle</th>
                        <th>Detalle Productos</th>
                        <th>Cantidad Total Productos</th>
                        <th
                            v-if="
                                inventoryConfiguration.confirm_inventory_transaction
                            "
                        >
                            Estado
                        </th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    <tr></tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.created_at }}</td>

                        <td>{{ row.warehouse }}</td>
                        <td>{{ row.warehouse_destination }}</td>
                        <td>{{ row.description }}</td>
                        <td>
                            <el-popover
                                placement="right"
                                trigger="click"
                                width="500"
                            >
                                <el-table :data="row.inventory">
                                    <el-table-column
                                        label="Producto"
                                        property="description"
                                        width="260"
                                    ></el-table-column>

                                    <el-table-column
                                        label="Cantidad"
                                        property="quantity"
                                    ></el-table-column>

                                    <el-table-column label="Series/Lotes">
                                        <template slot-scope="scope">
                                            <ul
                                                v-if="scope.row.lots"
                                                class="list-unstyled"
                                            >
                                                <li
                                                    v-for="(
                                                        item, index
                                                    ) in scope.row.lots"
                                                    :key="index"
                                                >
                                                    {{ item.series }}
                                                </li>
                                            </ul>
                                            <ul
                                                v-if="scope.row.lots_enabled"
                                                class="list-unstyled"
                                            >
                                                <li
                                                    v-for="(
                                                        item, index
                                                    ) in scope.row.lot_codes"
                                                    :key="index"
                                                >
                                                    {{ item.lot_code }}
                                                </li>
                                            </ul>
                                        </template>
                                    </el-table-column>
                                </el-table>
                                <el-button
                                    slot="reference"
                                    icon="el-icon-zoom-in"
                                ></el-button>
                            </el-popover>
                        </td>
                        <td>{{ row.quantity }}</td>
                        <td
                            v-if="
                                inventoryConfiguration.confirm_inventory_transaction
                            "
                        >
                            <template v-if="row.can_confirm && row.state == 1">
                                <div class="d-flex">
                                    <el-tooltip
                                        content="Aceptar traslado"
                                        placement="top"
                                    >
                                        <el-button
                                            @click.prevent="
                                                clickConfirm(row.id)
                                            "
                                            type="success"
                                            icon="el-icon-check"
                                            size="mini"
                                        ></el-button>
                                    </el-tooltip>
                                    <el-tooltip
                                        content="Rechazar traslado"
                                        placement="top"
                                    >
                                        <el-button
                                            @click.prevent="clickReject(row.id)"
                                            type="danger"
                                            icon="el-icon-error"
                                            size="mini"
                                        ></el-button
                                    ></el-tooltip>
                                </div>
                            </template>
                            <template v-else>
                                {{
                                    row.state == 1
                                        ? "Por aceptar"
                                        : row.state == 2
                                        ? "Aceptado"
                                        : "Rechazado"
                                }}
                            </template>
                        </td>
                        <td class="text-end">
                            <button
                                class="btn waves-effect waves-light btn-sm btn-info"
                                type="button"
                                @click.prevent="clickDownload('pdf', row.id)"
                            >
                                <i class="fa fa-file-pdf"></i>
                                PDF
                            </button>
                        </td>
                        <!--<td class="text-end">
                                         <button type="button" class="btn waves-effect waves-light btn-sm btn-info"
                                                @click.prevent="clickCreate(row.id)">Editar</button>
                                        <button type="button" class="btn waves-effect waves-light btn-sm btn-danger"
                                                @click.prevent="clickDelete(row.id)">Eliminar</button>
                        </td>-->
                    </tr>
                </data-table>
            </div>

            <inventories-form
                :recordId="recordId"
                :showDialog.sync="showDialog"
            ></inventories-form>
        </div>
    </div>
</template>

<script>
import DataTable from "../../../../../../resources/js/components/DataTableTransfers.vue";
import { deletable } from "../../../../../../resources/js/mixins/deletable";
import InventoriesForm from "./form.vue";

export default {
    props: ["inventoryConfiguration"],
    components: { DataTable, InventoriesForm },
    mixins: [deletable],
    data() {
        return {
            title: null,
            showDialog: false,
            resource: "transfers",
            recordId: null,
            typeTransaction: null,
        };
    },
    created() {
        this.title = "Traslados";
        // console.log("🚀 ~ created ~ this.inventoryConfiguration:", this.inventoryConfiguration)
    },
    methods: {
        async clickConfirm(id) {
            this.$confirm("¿Está seguro de aceptar el traslado?", "Aceptar", {
                confirmButtonText: "Aceptar",
                cancelButtonText: "Cancelar",
                type: "warning",
            })
                .then(async () => {
                    const response = await this.$http(
                        `/transfers/accept-transfer/${id}`
                    );
                    if (response.data.success) {
                        this.$eventHub.$emit("reloadData");
                        this.$message.success(
                            "Traslado aceptado correctamente"
                        );
                    }
                })
                .catch(() => {});
        },
        clickReject(id) {
            this.$confirm("¿Está seguro de rechazar el traslado?", "Rechazar", {
                confirmButtonText: "Rechazar",
                cancelButtonText: "Cancelar",
                type: "warning",
            })
                .then(async () => {
                    const response = await this.$http(
                        `/transfers/reject-transfer/${id}`
                    );
                    if (response.data.success) {
                        this.$eventHub.$emit("reloadData");
                        this.$message.success(
                            "Traslado rechazado correctamente"
                        );
                    }
                })
                .catch(() => {});
        },
        clickCreate(recordId = null) {
            location.href = `/${this.resource}/create`;
            //this.recordId = recordId
            //this.showDialog = true
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
        clickDownload(type, id) {
            window.open(`/${this.resource}/download/${type}/${id}`, "_blank");
        },
    },
};
</script>
