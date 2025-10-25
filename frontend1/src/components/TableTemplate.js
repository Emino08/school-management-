import React, { useState } from 'react';
import { StyledTableRow } from './styles';
import { Table, TableBody, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { FiChevronLeft, FiChevronRight } from 'react-icons/fi';

const TableTemplate = ({ buttonHaver: ButtonHaver, columns, rows }) => {
    const [page, setPage] = useState(0);
    const [rowsPerPage, setRowsPerPage] = useState(5);

    // Ensure rows is always an array
    const safeRows = Array.isArray(rows) ? rows : [];

    const handleChangePage = (newPage) => {
        setPage(newPage);
    };

    const handleChangeRowsPerPage = (value) => {
        setRowsPerPage(parseInt(value, 10));
        setPage(0);
    };

    const totalPages = Math.ceil(safeRows.length / rowsPerPage);

    return (
        <div className="w-full">
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-black hover:bg-black">
                            {columns.map((column) => (
                                <th
                                    key={column.id}
                                    className="text-white px-4 py-3 text-left text-sm font-semibold"
                                    style={{ minWidth: column.minWidth }}
                                >
                                    {column.label}
                                </th>
                            ))}
                            <th className="text-white px-4 py-3 text-center text-sm font-semibold">
                                Actions
                            </th>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {safeRows
                            .slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage)
                            .map((row) => {
                                return (
                                    <StyledTableRow key={row.id}>
                                        {columns.map((column) => {
                                            const value = row[column.id];
                                            return (
                                                <td key={column.id} className="px-4 py-3 text-sm">
                                                    {column.format && typeof value === 'number'
                                                        ? column.format(value)
                                                        : value}
                                                </td>
                                            );
                                        })}
                                        <td className="px-4 py-3 text-center">
                                            <ButtonHaver row={row} />
                                        </td>
                                    </StyledTableRow>
                                );
                            })}
                    </TableBody>
                </Table>
            </div>
            <div className="flex items-center justify-between px-4 py-4 border-t">
                <div className="flex items-center space-x-2">
                    <span className="text-sm text-gray-700">Rows per page:</span>
                    <Select value={rowsPerPage.toString()} onValueChange={handleChangeRowsPerPage}>
                        <SelectTrigger className="w-[70px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="5">5</SelectItem>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="flex items-center space-x-2">
                    <span className="text-sm text-gray-700">
                        {page * rowsPerPage + 1}-{Math.min((page + 1) * rowsPerPage, safeRows.length)} of{' '}
                        {safeRows.length}
                    </span>
                    <div className="flex space-x-1">
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => handleChangePage(page - 1)}
                            disabled={page === 0}
                        >
                            <FiChevronLeft className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => handleChangePage(page + 1)}
                            disabled={page >= totalPages - 1}
                        >
                            <FiChevronRight className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TableTemplate;